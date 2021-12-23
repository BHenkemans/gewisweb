<?php

namespace User\Authentication\Adapter;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;
use RuntimeException;
use User\Mapper\User as UserMapper;
use User\Authentication\Service\LoginAttempt as LoginAttemptService;

class PinMapper implements AdapterInterface
{
    /**
     * Mapper.
     *
     * @var UserMapper
     */
    protected UserMapper $mapper;

    /**
     * User Service
     * (for logging failed login attempts).
     *
     * @var LoginAttemptService
     */
    protected LoginAttemptService $loginAttemptService;

    /**
     * Lidnr.
     *
     * @var string
     */
    protected string $lidnr;

    /**
     * Pincode.
     *
     * @var string
     */
    protected string $pincode;

    /**
     * Constructor.
     *
     * @param LoginAttemptService $loginAttemptService
     * @param UserMapper $mapper
     */
    public function __construct(
        LoginAttemptService $loginAttemptService,
        UserMapper $mapper,
    ) {
        $this->loginAttemptService = $loginAttemptService;
        $this->mapper = $mapper;
    }

    /**
     * Try to authenticate.
     *
     * @return Result
     */
    public function authenticate(): Result
    {
        throw new RuntimeException("Legacy service is not available for PinMapper Auth.");
    }

    /**
     * Sets the credentials used to authenticate.
     *
     * @param string $lidnr
     * @param string $pincode
     */
    public function setCredentials(
        string $lidnr,
        string $pincode,
    ): void {
        $this->lidnr = $lidnr;
        $this->pincode = $pincode;
    }

    /**
     * Get the mapper.
     *
     * @return UserMapper
     */
    public function getMapper(): UserMapper
    {
        return $this->mapper;
    }
}
