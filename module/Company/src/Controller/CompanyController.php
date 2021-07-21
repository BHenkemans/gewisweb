<?php

namespace Company\Controller;

use Company\Service\{
    Company as CompanyService,
    CompanyQuery as CompanyQueryService,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;

class CompanyController extends AbstractActionController
{
    /**
     * @var CompanyService
     */
    private CompanyService $companyService;

    /**
     * @var CompanyQueryService
     */
    private CompanyQueryService $companyQueryService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * CompanyController constructor.
     *
     * @param CompanyService $companyService
     * @param CompanyQueryService $companyQueryService
     * @param Translator $translator
     */
    public function __construct(
        CompanyService $companyService,
        CompanyQueryService $companyQueryService,
        Translator $translator
    ) {
        $this->companyService = $companyService;
        $this->companyQueryService = $companyQueryService;
        $this->translator = $translator;
    }

    /**
     * Action to display a list of all nonhidden companies.
     */
    public function listAction()
    {
        $featuredPackage = $this->companyService->getFeaturedPackage();
        if (null === $featuredPackage) {
            return new ViewModel(
                [
                    'companyList' => $this->companyService->getCompanyList(),
                    'translator' => $this->translator,
                ]
            );
        }

        return new ViewModel(
            [
                'companyList' => $this->companyService->getCompanyList(),
                'translator' => $this->translator,
                'featuredCompany' => $featuredPackage->getCompany(),
                'featuredPackage' => $featuredPackage,
            ]
        );
    }

    public function showAction()
    {
        $companyName = $this->params('slugCompanyName');
        $company = $this->companyService->getCompanyBySlugName($companyName);

        if (!is_null($company)) {
            if (!$company->isHidden()) {
                return new ViewModel(
                    [
                        'company' => $company,
                        'translator' => $this->translator,
                    ]
                );
            }
        }

        return $this->notFoundAction();
    }

    /**
     * Action that shows the 'company in the spotlight' and the article written by the company in the current language.
     */
    public function spotlightAction()
    {
        $translator = $this->translator;

        $featuredPackage = $this->companyService->getFeaturedPackage();
        if (!is_null($featuredPackage)) {
            // jobs for a single company
            return new ViewModel(
                [
                    'company' => $featuredPackage->getCompany(),
                    'featuredPackage' => $featuredPackage,
                    'translator' => $translator,
                ]
            );
        }

        // There is no company is the spotlight, so throw a 404
        return $this->notFoundAction();
    }

    /**
     * Action that displays a list of all jobs (facaturebank) or a list of jobs for a company.
     */
    public function jobListAction()
    {
        $category = $this->companyService->categoryForSlug($this->params('category'));

        if (is_null($category)) {
            return $this->notFoundAction();
        }

        $viewModel = new ViewModel(
            [
                'category' => $category,
                'translator' => $this->translator,
            ]
        );

        // A job can be a thesis/internship/etc.
        $jobCategory = (null != $category->getLanguageNeutralId()) ? $category->getSlug() : null;

        if ($companyName = $this->params('slugCompanyName', null)) {
            // Retrieve published jobs for one specific company
            $jobs = $this->companyQueryService->getActiveJobList(
                [
                    'jobCategory' => $jobCategory,
                    'companySlugName' => $companyName,
                ]
            );

            return $viewModel->setVariables(
                [
                    'jobList' => $jobs,
                    'company' => $this->companyService->getCompanyBySlugName($companyName),
                ]
            );
        }

        // Retrieve all published jobs
        $jobs = $this->companyQueryService->getActiveJobList(
            [
                'jobCategory' => $jobCategory,
            ]
        );

        // Shuffle order to avoid bias
        shuffle($jobs);

        return $viewModel->setVariables(
            [
                'jobList' => $jobs,
            ]
        );
    }

    /**
     * Action to list a single job of a certain company.
     */
    public function jobsAction()
    {
        $jobName = $this->params('slugJobName');
        $companyName = $this->params('slugCompanyName');
        $category = $this->companyService->categoryForSlug($this->params('category'));
        if (null !== $jobName) {
            $jobs = $this->companyQueryService->getJobs(
                [
                    'companySlugName' => $companyName,
                    'jobSlug' => $jobName,
                    'jobCategory' => (null !== $category->getLanguageNeutralId()) ? $category->getSlug() : null,
                ]
            );
            if (!empty($jobs)) {
                if ($jobs[0]->isActive()) {
                    return new ViewModel(
                        [
                            'job' => $jobs[0],
                            'translator' => $this->translator,
                            'category' => $category,
                        ]
                    );
                }
            }

            return $this->notFoundAction();
        }

        return new ViewModel(
            [
                'activeJobList' => $this->companyQueryService->getActiveJobList(),
                'translator' => $this->translator,
            ]
        );
    }
}
