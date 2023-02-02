<?php

namespace User\Service;

use Company\Mapper\Company as CompanyMapper;
use Company\Model\Company as CompanyModel;
use DateInterval;
use DateTime;
use Decision\Mapper\Member as MemberMapper;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Mvc\I18n\Translator;
use RuntimeException;
use User\Authentication\{
    Adapter\CompanyUserAdapter,
    Adapter\UserAdapter,
    AuthenticationService,
};
use User\Form\{
    Activate as ActivateForm,
    CompanyUserLogin as CompanyUserLoginForm,
    CompanyUserReset as CompanyUserResetForm,
    UserLogin as UserLoginForm,
    Password as PasswordForm,
    Register as RegisterForm,
    Reset as ResetForm,
};
use User\Mapper\{
    NewCompanyUser as NewCompanyUserMapper,
    NewUser as NewUserMapper,
    User as UserMapper,
};
use User\Model\{
    CompanyUser as CompanyUserModel,
    NewCompanyUser as NewCompanyUserModel,
    NewUser as NewUserModel,
    User as UserModel,
};
use User\Permissions\NotAllowedException;
use User\Service\Email as EmailService;

/**
 * User service.
 */
class User
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly Bcrypt $bcrypt,
        private readonly AuthenticationService $userAuthService,
        private readonly AuthenticationService $companyUserAuthService,
        private readonly EmailService $emailService,
        private readonly UserMapper $userMapper,
        private readonly NewUserMapper $newUserMapper,
        private readonly NewCompanyUserMapper $newCompanyUserMapper,
        private readonly CompanyMapper $companyMapper,
        private readonly MemberMapper $memberMapper,
        private readonly RegisterForm $registerForm,
        private readonly ActivateForm $activateFormCompanyUser,
        private readonly ActivateForm $activateFormUser,
        private readonly UserLoginForm $userLoginForm,
        private readonly CompanyUserLoginForm $companyUserLoginForm,
        private readonly CompanyUserResetForm $companyUserResetForm,
        private readonly PasswordForm $passwordFormCompanyUser,
        private readonly PasswordForm $passwordFormUser,
        private readonly ResetForm $resetForm,
    ) {
    }

    /**
     * Activate a user.
     */
    public function activate(
        array $data,
        NewCompanyUserModel|NewUserModel $newUser,
    ): bool {
        if ($newUser instanceof NewCompanyUserModel) {
            $adapter = $this->companyUserAuthService->getAdapter();
        } else {
            $adapter = $this->userAuthService->getAdapter();
        }

        $user = $adapter->getMapper()->find($newUser->getId());
        if (null === $user) {
            // The user does not yet exist.
            if ($newUser instanceof NewCompanyUserModel) {
                $user = new CompanyUserModel($newUser);
            } else {
                $user = new UserModel($newUser);
            }
        }

        $user->setPassword($this->bcrypt->create($data['password']));

        // this will also save a user with a lost password
        $adapter->getMapper()->persist($user);
        $adapter->getMapper()->remove($newUser);

        return true;
    }

    /**
     * Register a user.
     *
     * Will also send an email to the user.
     *
     * @param array $data Registration data
     *
     * @return NewUserModel|null New registered user. Null when the user could not be registered.
     */
    public function register(array $data): ?NewUserModel
    {
        $form = $this->registerForm;
        $form->setData($data);
        // TODO: Move form validation to controller.
        if (!$form->isValid()) {
            return null;
        }

        // get the member
        $data = $form->getData();
        $member = $this->memberMapper->findByLidnr($data['lidnr']);

        if (null === $member) {
            $form->setError(RegisterForm::ERROR_MEMBER_NOT_EXISTS);

            return null;
        }

        // Check if the member has an e-mail address.
        if (null === $member->getEmail()) {
            $form->setError(RegisterForm::ERROR_NO_EMAIL);

            return null;
        }

        // check if the member already has a corresponding user.
        $user = $this->userMapper->find($member->getLidnr());
        if (null !== $user) {
            $form->setError(RegisterForm::ERROR_USER_ALREADY_EXISTS);

            return null;
        }

        $newUser = $this->newUserMapper->getByLidnr($data['lidnr']);
        if (null !== $newUser) {
            // Ensure that we only send the activation email every 20 minutes.
            $time = $newUser->getTime();
            $requiredInterval = (new DateTime())->sub(new DateInterval('PT1200S'));

            if ($time > $requiredInterval) {
                $form->setError(RegisterForm::ERROR_ALREADY_REGISTERED);

                return null;
            }

            $this->newUserMapper->deleteByMember($member);
        }

        // save the data, and send email
        $newUser = new NewUserModel($member);
        $newUser->setCode($this->generateCode());
        $newUser->setTime(new DateTime());

        $this->newUserMapper->persist($newUser);

        $this->emailService->sendRegisterEmail($newUser, $member);

        return $newUser;
    }

    /**
     * Register a company.
     *
     * TODO: Re-enable functionality once company module overhaul is finished.
     */
    public function registerCompanyUser(CompanyModel $company): void
    {
        // TODO: Re-enable functionality once company module overhaul is finished.
        // $newCompanyUser = new NewCompanyUserModel($company);
        // $newCompanyUser->setCode($this->generateCode());
        // $newCompanyUser->setTime(new DateTime());

        // $this->newCompanyUserMapper->persist($newCompanyUser);

        // $this->emailService->sendCompanyRegisterMail($newCompanyUser, $company);
    }

    /**
     * Request a password reset for a member.
     *
     * Will also send an email to the user.
     */
    public function resetMember(array $data): void
    {
        $user = $this->userMapper->find($data['lidnr']);

        if (null !== $user) {
            $member = $user->getMember();

            if (strtolower($member->getEmail()) === strtolower($data['email'])) {
                $newUser = $this->newUserMapper->getByLidnr($data['lidnr']);

                if (null !== $newUser) {
                    // Ensure that we only send the password reset e-mail every 20 minutes at most.
                    $time = $newUser->getTime();
                    $requiredInterval = (new DateTime())->sub(new DateInterval('PT1200S'));

                    if ($time > $requiredInterval) {
                        return;
                    }

                    $this->newUserMapper->deleteByMember($member);
                }

                // create new activation
                $newUser = new NewUserModel($member);
                $newUser->setCode($this->generateCode());
                $newUser->setTime(new DateTime());

                $this->newUserMapper->persist($newUser);

                $this->emailService->sendPasswordLostMail($newUser, $member);
            }
        }
    }

    /**
     * Request a password reset for a company.
     */
    public function resetCompany(array $data): void
    {
        $company = $this->companyMapper->findCompanyByRepresentativeEmail($data['email']);

        if (null === $company) {
            return;
        }

        $this->newCompanyUserMapper->deleteByCompany($company);

        $newCompanyUser = new NewCompanyUserModel($company);
        $newCompanyUser->setCode($this->generateCode());

        $this->newCompanyUserMapper->persist($newCompanyUser);
        $this->emailService->sendCompanyPasswordLostMail($newCompanyUser, $company);
    }

    /**
     * Change the password of a user.
     */
    public function changePassword(array $data): bool
    {
        $user = $this->aclService->getIdentity();

        if ($user instanceof CompanyUserModel) {
            /** @var CompanyUserAdapter $adapter */
            $adapter = $this->companyUserAuthService->getAdapter();
            $type = 'company';
        } elseif ($user instanceof UserModel) {
            /** @var UserAdapter $adapter */
            $adapter = $this->userAuthService->getAdapter();
            $type = 'member';
        } else {
            throw new RuntimeException("Unexpected type of user while trying to change passwords");
        }

        if (!$adapter->verifyPassword($data['old_password'], $user->getPassword())) {
            $this->getPasswordForm($type)->setMessages([
                'old_password' => [
                    $this->translator->translate('Password incorrect'),
                ],
            ]);

            return false;
        }

        $user->setPassword($this->bcrypt->create($data['password']));
        $adapter->getMapper()->persist($user);

        return true;
    }

    /**
     * Log the user in.
     */
    public function userLogin(array $data): ?UserModel
    {
        // Try to authenticate the user.
        $this->userAuthService->setRememberMe($data['remember'] === '1');
        $result = $this->userAuthService->authenticate($data['login'], $data['password']);

        // Check if authentication was successful.
        if (!$result->isValid()) {
            $form = $this->getUserLoginForm();
            $form->setResult($result);

            return null;
        }

        $identity = $this->userAuthService->getIdentity();
        if ($identity instanceof UserModel) {
            return $identity;
        }
        return null;
    }

    /**
     * Log the company in.
     */
    public function companyLogin(array $data): ?CompanyUserModel
    {
        // Try to authenticate the company user.
        $result = $this->companyUserAuthService->authenticate($data['email'], $data['password']);

        // Check if authentication was successful.
        if (!$result->isValid()) {
            $form = $this->getCompanyUserLoginForm();
            $form->setResult($result);

            return null;
        }

        $identity = $this->companyUserAuthService->getIdentity();
        if ($identity instanceof CompanyUserModel) {
            return $identity;
        }
        return null;
    }

    /**
     * Log the user out.
     */
    public function logout(): void
    {
        // clear the user identity
        $this->userAuthService->clearIdentity();
        $this->companyUserAuthService->clearIdentity();
    }

    /**
     * Get the new user.
     *
     * @param string $code
     *
     * @return NewUserModel|null
     */
    public function getNewUser(string $code): ?NewUserModel
    {
        return $this->newUserMapper->getByCode($code);
    }

    /**
     * Get the new company user.
     *
     * @param string $code
     *
     * @return NewCompanyUserModel|null
     */
    public function getNewCompanyUser(string $code): ?NewCompanyUserModel
    {
        return $this->newCompanyUserMapper->getByCode($code);
    }

    /**
     * Generate an activation code for the user.
     *
     * @param int $length
     *
     * @return string
     */
    public static function generateCode(int $length = 48): string
    {
        $ret = '';
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        for ($i = 0; $i < $length; ++$i) {
            $ret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $ret;
    }

    /**
     * Get the activate form.
     *
     * @return ActivateForm Activate form
     */
    public function getActivateForm(string $userType): ActivateForm
    {
        if ('company' === $userType) {
            return $this->activateFormCompanyUser;
        }

        return $this->activateFormUser;
    }

    /**
     * Get the register form.
     *
     * @return RegisterForm Register form
     */
    public function getRegisterForm(): RegisterForm
    {
        return $this->registerForm;
    }

    /**
     * Get the password form.
     *
     * @return PasswordForm Password change form
     */
    public function getPasswordForm(string $userType): PasswordForm
    {
        if (!$this->aclService->isAllowed('password_change', 'user')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to change your password')
            );
        }

        if ('company' === $userType) {
            return $this->passwordFormCompanyUser;
        }

        return $this->passwordFormUser;
    }

    /**
     * Get the reset form.
     *
     * @return ResetForm
     */
    public function getResetForm(): ResetForm
    {
        return $this->resetForm;
    }

    /**
     * Get the login form.
     *
     * @return UserLoginForm UserLogin form
     */
    public function getUserLoginForm(): UserLoginForm
    {
        return $this->userLoginForm;
    }

    /**
     * @return CompanyUserLoginForm
     */
    public function getCompanyUserLoginForm(): CompanyUserLoginForm
    {
        return $this->companyUserLoginForm;
    }

    /**
     * @return CompanyUserResetForm
     */
    public function getCompanyUserResetForm(): CompanyUserResetForm
    {
        return $this->companyUserResetForm;
    }
}
