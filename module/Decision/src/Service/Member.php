<?php

namespace Decision\Service;

use Decision\Mapper\Authorization;
use Decision\Model\{
    Meeting,
    Member as MemberModel,
};
use Laminas\Code\Exception\InvalidArgumentException;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

/**
 * Member service.
 */
class Member
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var \Decision\Mapper\Member
     */
    private $memberMapper;

    /**
     * @var Authorization
     */
    private $authorizationMapper;

    /**
     * @var AclService
     */
    private AclService $aclService;

    public function __construct(
        Translator $translator,
        \Decision\Mapper\Member $memberMapper,
        Authorization $authorizationMapper,
        AclService $aclService,
    ) {
        $this->translator = $translator;
        $this->memberMapper = $memberMapper;
        $this->authorizationMapper = $authorizationMapper;
        $this->aclService = $aclService;
    }

    public const MIN_SEARCH_QUERY_LENGTH = 2;

    /**
     * Returns is the member is active.
     *
     * @return bool
     */
    public function isActiveMember()
    {
        return $this->aclService->isAllowed('edit', 'organ');
    }

    public function findMemberByLidNr($lidnr)
    {
        if (!$this->aclService->isAllowed('view', 'member')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view members.'));
        }

        return $this->memberMapper->findByLidnr($lidnr);
    }

    /**
     * Get the members of which their birthday falls in the next $days days.
     *
     * When $days equals 0 or isn't given, it will give all birthdays of today.
     *
     * @param int $days the number of days to look ahead
     *
     * @return array Of members sorted by birthday
     */
    public function getBirthdayMembers($days = 0)
    {
        if (0 == $days && !$this->aclService->isAllowed('birthdays_today', 'member')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the list of today\'s birthdays.')
            );
        }

        if ($days > 0 && !$this->aclService->isAllowed('birthdays', 'member')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the list of birthdays.')
            );
        }

        return $this->memberMapper->findBirthdayMembers($days);
    }

    /**
     * Get the organs a member is part of.
     *
     * @return array
     */
    public function getOrgans(MemberModel $member)
    {
        return $this->memberMapper->findOrgans($member);
    }

    /**
     * Find a member by (part of) its name.
     *
     * @param string $query (part of) the full name of a member
     * @pre $name must be at least MIN_SEARCH_QUERY_LENGTH
     *
     * @return array
     */
    public function searchMembersByName($query)
    {
        if (strlen($query) < self::MIN_SEARCH_QUERY_LENGTH) {
            throw new InvalidArgumentException(
                $this->translator->translate('Name must be at least ' . self::MIN_SEARCH_QUERY_LENGTH . ' characters')
            );
        }

        if (!$this->aclService->isAllowed('search', 'member')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to search for members.'));
        }

        return $this->memberMapper->searchByName($query);
    }

    /**
     * Find a member by (part of) its name.
     *
     * @param MemberModel $member
     * @param Meeting $meeting
     * @return bool
     * @pre $name must be at least MIN_SEARCH_QUERY_LENGTH
     *
     */
    public function canAuthorize($member, $meeting)
    {
        $maxAuthorizations = 2;

        $meetingNumber = $meeting->getNumber();
        $lidnr = $member->getLidnr();
        $authorizations = $this->authorizationMapper->findRecipientAuthorization($meetingNumber, $lidnr);

        if (count($authorizations) < $maxAuthorizations) {
            return true;
        }

        return false;
    }
}
