<?php

namespace Education\Controller;

use Education\Form\SummaryUpload as SummaryUploadForm;
use Education\Service\Exam as ExamService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    JsonModel,
    ViewModel,
};
use Laminas\View\View;

class AdminController extends AbstractActionController
{
    /**
     * @var ExamService
     */
    private ExamService $examService;

    /**
     * @var SummaryUploadForm
     */
    private SummaryUploadForm $summaryUploadForm;

    /**
     * @var array
     */
    private array $educationTempConfig;

    /**
     * AdminController constructor.
     *
     * @param ExamService $examService
     * @param SummaryUploadForm $summaryUploadForm
     * @param array $educationTempConfig
     */
    public function __construct(
        ExamService $examService,
        SummaryUploadForm $summaryUploadForm,
        array $educationTempConfig
    ) {
        $this->examService = $examService;
        $this->summaryUploadForm = $summaryUploadForm;
        $this->educationTempConfig = $educationTempConfig;
    }

    public function indexAction()
    {
        return new View();
    }

    public function addCourseAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($this->examService->addCourse($request->getPost())) {
                $this->getResponse()->setStatusCode(200);

                return new ViewModel(
                    [
                        'form' => $this->examService->getAddCourseForm(),
                        'success' => true,
                    ]
                );
            }
            $this->getResponse()->setStatusCode(400);

            return new ViewModel(
                [
                    'form' => $this->examService->getAddCourseForm(),
                    'success' => false,
                ]
            );
        }

        return new ViewModel(
            [
                'form' => $this->examService->getAddCourseForm(),
            ]
        );
    }

    public function bulkExamAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($this->examService->tempExamUpload($request->getPost(), $request->getFiles())) {
                return new ViewModel(
                    [
                        'success' => true,
                    ]
                );
            } else {
                $this->getResponse()->setStatusCode(500);

                return new ViewModel(
                    [
                        'success' => false,
                    ]
                );
            }
        }

        return new ViewModel(
            [
                'form' => $this->examService->getTempUploadForm(),
            ]
        );
    }

    public function bulkSummaryAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($this->examService->tempSummaryUpload($request->getPost(), $request->getFiles())) {
                return new ViewModel(
                    [
                        'success' => true,
                    ]
                );
            } else {
                $this->getResponse()->setStatusCode(500);

                return new ViewModel(
                    [
                        'success' => false,
                    ]
                );
            }
        }

        return new ViewModel(
            [
                'form' => $this->examService->getTempUploadForm(),
            ]
        );
    }

    /**
     * Edit several exams in bulk.
     */
    public function editExamAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() && $this->examService->bulkExamEdit($request->getPost())) {
            return new ViewModel(
                [
                    'success' => true,
                ]
            );
        }

        $config = $this->educationTempConfig;

        return new ViewModel(
            [
                'form' => $this->examService->getBulkExamForm(),
                'config' => $config,
            ]
        );
    }

    /**
     * Edit summaries in bulk.
     */
    public function editSummaryAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() && $this->examService->bulkSummaryEdit($request->getPost())) {
            return new ViewModel(
                [
                    'success' => true,
                ]
            );
        }

        $config = $this->educationTempConfig;

        return new ViewModel(
            [
                'form' => $this->examService->getBulkSummaryForm(),
                'config' => $config,
            ]
        );
    }

    public function deleteTempAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->examService->deleteTempExam(
                $this->params()->fromRoute('filename'),
                $this->params()->fromRoute('type')
            );

            return new JsonModel(['success' => 'true']);
        }

        return $this->notFoundAction();
    }
}