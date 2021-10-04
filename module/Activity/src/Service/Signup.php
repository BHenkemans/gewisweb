<?php

namespace Activity\Service;

use Activity\Mapper\{
    SignupFieldValue,
    SignupOption,
};
use Activity\Model\{
    ExternalSignup as ExternalSignupModel,
    Signup as SignupModel,
    SignupFieldValue as SignupFieldValueModel,
    SignupList as SignupListModel,
    UserSignup as UserSignupModel,
};
use DateTime;
use Doctrine\ORM\{
    EntityManager,
    OptimisticLockException,
    ORMException,
};
use Laminas\Mvc\I18n\Translator;
use User\Model\User as UserModel;
use User\Permissions\NotAllowedException;

class Signup
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var \Activity\Mapper\Signup
     */
    private $signupMapper;

    /**
     * @var SignupOption
     */
    private $signupOptionMapper;

    /**
     * @var SignupFieldValue
     */
    private $signupFieldValueMapper;
    private AclService $aclService;

    public function __construct(
        Translator $translator,
        EntityManager $entityManager,
        \Activity\Mapper\Signup $signupMapper,
        SignupOption $signupOptionMapper,
        SignupFieldValue $signupFieldValueMapper,
        AclService $aclService
    ) {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->signupMapper = $signupMapper;
        $this->signupOptionMapper = $signupOptionMapper;
        $this->signupFieldValueMapper = $signupFieldValueMapper;
        $this->aclService = $aclService;
    }

    /**
     * Return the form for signing up in the preferred language, if available.
     * Otherwise, it returns it in the available language.
     *
     * @param SignupListModel $signupList
     *
     * @return \Activity\Form\Signup
     *
     * @throws NotAllowedException
     */
    public function getForm($signupList)
    {
        if (!$this->aclService->isAllowed('signup', $signupList)) {
            throw new NotAllowedException(
                $this->translator->translate('You need to be logged in to sign up for this activity')
            );
        }

        $form = new \Activity\Form\Signup();
        $form->initialiseForm($signupList);

        return $form;
    }

    public function getExternalAdminForm($signupList)
    {
        if (!$this->aclService->isAllowed('adminSignup', $signupList)) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to use the external admin signup')
            );
        }

        $form = new \Activity\Form\Signup();
        $form->initialiseExternalAdminForm($signupList);

        return $form;
    }

    public function getExternalForm($signupList)
    {
        if (!$this->aclService->isAllowed('externalSignup', $signupList)) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to use the external signup')
            );
        }

        $form = new \Activity\Form\Signup();
        $form->initialiseExternalForm($signupList);

        return $form;
    }

    /**
     * Gets an array of the signed up users and the associated data.
     *
     * @return array
     */
    public function getSignedUpData(SignupListModel $signupList)
    {
        if (!$this->aclService->isAllowed('view', $signupList)) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the sign up data'));
        }

        $fieldValueMapper = $this->signupFieldValueMapper;
        $result = [];

        foreach ($signupList->getSignUps() as $signup) {
            $entry = [];
            $entry['member'] = $signup->getFullName();
            $entry['values'] = [];

            foreach ($fieldValueMapper->getFieldValuesBySignup($signup) as $fieldValue) {
                // If there is an option type, get the option object as a 'value'.
                $isOption = 3 === $fieldValue->getField()->getType();
                $value = $isOption ? $fieldValue->getOption() : $fieldValue->getValue();
                $entry['values'][$fieldValue->getField()->getId()] = $value;
            }

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Gets an array of the signed up users, but without the associated data.
     *
     * @return array
     */
    public function getSignedUpDataWithoutFields(SignupListModel $signupList)
    {
        if (!$this->aclService->isAllowed('view', $signupList)) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the sign up data'));
        }

        $result = [];

        foreach ($signupList->getSignUps() as $signup) {
            $entry = [];
            $entry['fullName'] = $signup->getFullName();
            $entry['email'] = $signup->getEmail();

            $entry['type'] = $this->translator->translate('External');

            if ($signup instanceof UserSignupModel) {
                $entry['type'] = $this->translator->translate('User');
            }

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Check if a member is signed up for an activity.
     *
     * @param SignupListModel $signupList
     * @param UserModel $user
     *
     * @return bool
     */
    public function isSignedUp(SignupListModel $signupList, UserModel $user): bool
    {
        if (!$this->aclService->isAllowed('checkUserSignedUp', 'signupList')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the activities'));
        }

        return $this->signupMapper->isSignedUp($signupList->getId(), $user->getLidnr());
    }

    /**
     * Get the ids of all activities which the current user is signed up for.
     *
     * @return array
     */
    public function getSignedUpActivityIds()
    {
        if (!$this->aclService->isAllowed('checkUserSignedUp', 'signupList')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view activities which you signed up for')
            );
        }

        $user = $this->aclService->getIdentityOrThrowException();
        $activitySignups = $this->signupMapper->getSignedUpActivities($user->getLidnr());
        $activities = [];

        foreach ($activitySignups as $activitySignup) {
            $activities[] = $activitySignup->getActivity()->getId();
        }

        return $activities;
    }

    /**
     * Sign a User up for an activity with the specified field values.
     */
    public function signUp(SignupListModel $signupList, array $fieldResults)
    {
        if (!$this->aclService->isAllowed('signup', 'signupList')) {
            throw new NotAllowedException(
                $this->translator->translate('You need to be logged in to sign up for this activity')
            );
        }

        $user = $this->aclService->getIdentityOrThrowException();
        $signup = new UserSignupModel();
        $signup->setUser($user);
        $this->createSignup($signup, $signupList, $fieldResults);
    }

    /**
     * Creates the generic parts of a signup.
     *
     * @param SignupModel $signup
     * @param SignupListModel $signupList
     * @param array $fieldResults
     * @return SignupModel
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function createSignup(SignupModel $signup, SignupListModel $signupList, array $fieldResults)
    {
        $signup->setSignupList($signupList);
        $optionMapper = $this->signupOptionMapper;
        $em = $this->entityManager;
        foreach ($signupList->getFields() as $field) {
            $fieldValue = new SignupFieldValueModel();
            $fieldValue->setField($field);
            $value = $fieldResults[$field->getId()];

            //Change the value into the actual format
            switch ($field->getType()) {
                case 0://'Text'
                case 2://'Number'
                    $fieldValue->setValue($value);
                    break;
                case 1://'Yes/No'
                    $fieldValue->setValue(($value) ? 'Yes' : 'No');
                    break;
                case 3://'Choice'
                    $fieldValue->setOption($optionMapper->find((int)$value));
                    break;
            }
            $fieldValue->setSignup($signup);
            $em->persist($fieldValue);
        }
        $em->persist($signup);
        $em->flush();

        return $signup;
    }

    /**
     * Sign an external user up for an activity, which the current user may admin.
     *
     * @param string $fullName
     * @param string $email
     *
     * @throws NotAllowedException
     */
    public function adminSignUp(SignupListModel $signupList, $fullName, $email, array $fieldResults)
    {
        if (!($this->aclService->isAllowed('adminSignup', $signupList))) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to subscribe an external user to this sign-up list')
            );
        }

        $this->manualSignUp($signupList, $fullName, $email, $fieldResults);
    }

    /**
     * Sign an external user up for an activity.
     *
     * @param string $fullName
     * @param string $email
     *
     * @throws NotAllowedException
     */
    protected function manualSignUp(SignupListModel $signupList, $fullName, $email, array $fieldResults)
    {
        $signup = new ExternalSignupModel();
        $signup->setEmail($email);
        $signup->setFullName($fullName);
        $this->createSignup($signup, $signupList, $fieldResults);
    }

    /**
     * Sign an external user up for an activity, allowed by a guest.
     *
     * @param string $fullName
     * @param string $email
     *
     * @throws NotAllowedException
     */
    public function externalSignUp(SignupListModel $signupList, $fullName, $email, array $fieldResults)
    {
        if (!($this->aclService->isAllowed('externalSignup', $signupList))) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to subscribe to this sign-up list')
            );
        }

        $this->manualSignUp($signupList, $fullName, $email, $fieldResults);
    }

    /**
     * Undo an activity sign up.
     */
    public function signOff(SignupListModel $signupList, UserModel $user)
    {
        if (!$this->aclService->isAllowed('signoff', 'signupList')) {
            throw new NotAllowedException(
                $this->translator->translate('You need to be logged in to sign off for this activity')
            );
        }

        $signUpMapper = $this->signupMapper;
        $signUp = $signUpMapper->getSignUp($signupList->getId(), $user->getLidnr());

        // If the user was not signed up, no need to signoff anyway
        if (is_null($signUp)) {
            return;
        }

        $this->removeSignUp($signUp);
    }

    protected function removeSignUp(SignupModel $signup)
    {
        $em = $this->entityManager;
        $em->remove($signup);
        $em->flush();
    }

    public function getNumberOfSubscribedMembers(SignupListModel $signupList)
    {
        return $this->signupMapper
            ->getNumberOfSignedUpMembers($signupList->getId())[1];
    }

    public function externalSignOff(ExternalSignupModel $signup)
    {
        if (
            !($this->aclService->isAllowed('adminSignup', 'activity') ||
            $this->aclService->isAllowed('adminSignup', $signup->getSignupList()))
        ) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to remove external signups for this activity')
            );
        }
        $this->removeSignUp($signup);
    }

    public static function isInSubscriptionWindow($openDate, $closeDate)
    {
        $currentTime = new DateTime();

        return $openDate < $currentTime && $currentTime < $closeDate;
    }

    /**
     * Is the currently logged in user allowed to signup.
     *
     * @return bool
     */
    public function isAllowedToSubscribe()
    {
        return $this->aclService->isAllowed('signup', 'signupList');
    }

    /**
     * Is the (guest) user allowed to use the external signup.
     *
     * @return bool
     */
    public function isAllowedToExternalSubscribe()
    {
        return $this->aclService->isAllowed('externalSignup', 'signupList');
    }

    public function isAllowedToViewSubscriptions()
    {
        return $this->aclService->isAllowed('view', 'signupList');
    }

    public function isAllowedToInternalSubscribe()
    {
        return $this->aclService->isAllowed('signup', 'signupList');
    }
}
