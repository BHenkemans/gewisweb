<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

class PollController extends AbstractActionController
{
    public function indexAction()
    {
        $poll = $this->getPollService()->getNewestPoll();
        if(!is_null($poll)) {
            $details = $this->getPollService()->getPollDetails($poll);

            $session = new SessionContainer('lang');

            return new ViewModel(array_merge($details, array(
                'poll' => $poll,
                'lang' => $session->lang
            )));
        } else {
            return new ViewModel();
        }
    }

    public function voteAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $pollService = $this->getPollService();
            $optionId = $request->getPost()['option'];
            $pollService->submitVote($pollService->getPollOption($optionId));
            $this->redirect()->toRoute('poll');
        }
    }

    public function historyAction()
    {
        $adapter = $this->getPollService()->getPaginatorAdapter();
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(10);

        $page = $this->params()->fromRoute('page');
        if($page) $paginator->setCurrentPageNumber($page);
        $session = new SessionContainer('lang');

        return new ViewModel(array(
            'paginator' => $paginator,
            'lang' => $session->lang
        ));
    }

    /**
     * Get the poll service.
     *
     * @return \Frontpage\Service\Poll
     */
    protected function getPollService()
    {
        return $this->getServiceLocator()->get('frontpage_service_poll');
    }
}
