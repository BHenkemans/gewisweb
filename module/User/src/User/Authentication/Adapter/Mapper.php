<?php

namespace User\Authentication\Adapter;

use User\Model\LoginAttempt;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Result;
use User\Mapper\User as UserMapper;
use User\Model\User as UserModel;
use Zend\Crypt\Password\Bcrypt;
use Application\Service\Legacy as LegacyService;
use User\Service\LoginAttempt as LoginAttemptService;

class Mapper implements AdapterInterface
{
    /**
     * Mapper.
     *
     * @var UserMapper
     */
    protected $mapper;

    /**
     * Email.
     *
     * @var string
     */
    protected $email;

    /**
     * Password.
     *
     * @var string
     */
    protected $password;

    /**
     * Bcrypt instance.
     *
     * @var Bcrypt
     */
    protected $bcrypt;

    /**
     * Legacy Service
     * (for checking logins against the old database)
     *
     * @var LegacyService
     */
    protected $legacyService;

    /**
     * User Service
     * (for logging failed login attempts)
     *
     * @var LoginAttemptService
     */
    protected $loginAttemptService;
    /**
     * @var mixed
     */
    private $login;

    /**
     * Constructor.
     *
     * @param Bcrypt $bcrypt
     */
    public function __construct(Bcrypt $bcrypt, LegacyService $legacyService, loginAttemptService $loginAttemptService)
    {
        $this->bcrypt = $bcrypt;
        $this->legacyService = $legacyService;
        $this->loginAttemptService = $loginAttemptService;
    }

    /**
     * Try to authenticate.
     *
     * @return Result
     */
    public function authenticate()
    {
        $user = $this->mapper->findByLogin($this->login);

        if (null === $user) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
                []
            );
        }

        $this->mapper->detach($user);

        if ($this->loginAttemptService->loginAttemptsExceeded(LoginAttempt::TYPE_NORMAL, $user)) {
            return new Result(
                Result::FAILURE,
                null,
                []
            );
        }

        if (!$this->verifyPassword($this->password, $user->getPassword(), $user)) {
            $this->loginAttemptService->logFailedLogin($user, LoginAttempt::TYPE_NORMAL);
            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                null,
                []
            );
        }

        return new Result(Result::SUCCESS, $user);
    }

    /**
     * Verify a password.
     *
     * @param string $password
     * @param string $hash
     * @param UserModel $user
     *
     * @return boolean
     */
    public function verifyPassword($password, $hash, $user = null)
    {
        if (strlen($hash) === 0) {
            return $this->legacyService->checkPassword($user, $password, $this->bcrypt);
        }

        if ($this->bcrypt->verify($password, $hash)) {
            return true;
        }

        return false;
    }

    /**
     * Set the credentials.
     *
     * @param array $data
     */
    public function setCredentials($data)
    {
        $this->login = $data['login'];
        $this->password = $data['password'];
    }

    /**
     * Get the mapper.
     *
     * @return UserMapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * Set the mapper.
     *
     * @param UserMapper $mapper
     */
    public function setMapper(UserMapper $mapper)
    {
        $this->mapper = $mapper;
    }
}
