<?php

namespace User\Controller;

use Decision\Service\MemberInfo as MemberInfoService;
use Laminas\Mvc\Controller\AbstractActionController;
use User\Service\AclService;

class ApiController extends AbstractActionController
{
    /**
     * @var MemberInfoService
     */
    private MemberInfoService $memberInfoService;

    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * ApiController constructor.
     *
     * @param MemberInfoService $memberInfoService
     * @param AclService $aclService
     */
    public function __construct(
        MemberInfoService $memberInfoService,
        AclService $aclService
    ) {
        $this->memberInfoService = $memberInfoService;
        $this->aclService = $aclService;
    }

    public function validateAction()
    {
        if ($this->aclService->hasIdentity()) {
            $identity = $this->aclService->getIdentity();
            $response = $this->getResponse();
            $response->setStatusCode(200);
            $headers = $response->getHeaders();
            $headers->addHeaderLine('GEWIS-MemberID', $identity->getLidnr());
            if (null != $identity->getMember()) {
                $member = $identity->getMember();
                $name = $member->getFullName();
                $headers->addHeaderLine('GEWIS-MemberName', $name);
                $headers->addHeaderLine('GEWIS-MemberEmail', $member->getEmail());
                $memberships = $this->memberInfoService->getOrganMemberships($member);
                $headers->addHeaderLine('GEWIS-MemberGroups', implode(',', array_keys($memberships)));

                return $response;
            }
            $headers->addHeaderLine('GEWIS-MemberName', '');

            return $response;
        }
        $response = $this->getResponse();
        $response->setStatusCode(401);

        return $response;
    }
}