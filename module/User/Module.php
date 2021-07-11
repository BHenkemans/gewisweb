<?php

namespace User;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use User\Authentication\Adapter\Mapper;
use User\Authentication\Adapter\PinMapper;
use User\Form\Activate;
use User\Form\ApiToken;
use User\Form\Login;
use User\Form\Password;
use User\Form\Register;
use User\Mapper\ApiUser;
use User\Mapper\LoginAttempt;
use User\Mapper\NewUser;
use User\Mapper\Session;
use User\Permissions\Assertion\IsBoardMember;
use User\Service\ApiApp;
use User\Service\Email;
use User\Service\Factory\ApiAppFactory;
use Zend\Authentication\AuthenticationService;
use Zend\Crypt\Password\Bcrypt;
use Zend\Http\PhpEnvironment\RemoteAddress;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;
use Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;

use User\Permissions\NotAllowedException;
use User\Model\User;

class Module
{
    /**
     * Bootstrap.
     *
     * @var MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e)
    {
        $em = $e->getApplication()->getEventManager();

        // check if the user has a valid API token
        $request = $e->getRequest();

        if (($request instanceof HttpRequest) && $request->getHeaders()->has('X-Auth-Token')) {
            // check if this is a valid token
            $token = $request->getHeader('X-Auth-Token')
                ->getFieldValue();

            $sm = $e->getApplication()->getServiceManager();
            $service = $sm->get('user_service_apiuser');
            $service->verifyToken($token);
        }

        // this event listener will turn the request into '403 Forbidden' when
        // there is a NotAllowedException
        $em->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            if (
                $e->getError() == 'error-exception'
                && $e->getParam('exception', null) != null
                && $e->getParam('exception') instanceof NotAllowedException
            ) {
                $form = $e->getApplication()->getServiceManager()->get('user_form_login');
                $e->getResult()->setVariable('form', $form);
                $e->getResult()->setTemplate((APP_ENV === 'production' ? 'error/403' : 'error/debug/403'));
                $e->getResponse()->setStatusCode(403);
            }
        }, -100);
    }


    /**
     * Get the autoloader configuration.
     */
    public function getAutoloaderConfig()
    {
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig()
    {
        return [
            'aliases' => [
                'Zend\Authentication\AuthenticationService' => 'user_auth_service'
            ],

            'factories' => [
                'user_service_user' => function ($sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $bcrypt = $sm->get('user_bcrypt');
                    $authService = $sm->get('user_auth_service');
                    $pinMapper = $sm->get('user_pin_auth_service');
                    $authStorage = $sm->get('user_auth_storage');
                    $emailService = $sm->get('user_service_email');
                    $acl = $sm->get('acl');
                    $userMapper = $sm->get('user_mapper_user');
                    $newUserMapper = $sm->get('user_mapper_newuser');
                    $memberMapper = $sm->get('decision_mapper_member');
                    $registerForm = $sm->get('user_form_register');
                    $activateForm = $sm->get('user_form_activate');
                    $loginForm = $sm->get('user_form_login');
                    $passwordForm = $sm->get('user_form_password');
                    return new Service\User(
                        $translator,
                        $userRole,
                        $bcrypt,
                        $authService,
                        $pinMapper,
                        $authStorage,
                        $emailService,
                        $acl,
                        $userMapper,
                        $newUserMapper,
                        $memberMapper,
                        $registerForm,
                        $activateForm,
                        $loginForm,
                        $passwordForm
                    );
                },
                'user_service_loginattempt' => function ($sm) {
                    $remoteAddress = $sm->get('user_remoteaddress');
                    $entityManager = $sm->get('user_doctrine_em');
                    $loginAttemptMapper = $sm->get('user_mapper_loginattempt');
                    $userMapper = $sm->get('user_mapper_user');
                    $rateLimitConfig = $sm->get('config')['login_rate_limits'];
                    return new Service\LoginAttempt(
                        $remoteAddress,
                        $entityManager,
                        $loginAttemptMapper,
                        $userMapper,
                        $rateLimitConfig
                    );
                },
                'user_service_apiuser' => function ($sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('acl');
                    $apiUserMapper = $sm->get('user_mapper_apiuser');
                    $apiTokenForm = $sm->get('user_form_apitoken');
                    return new Service\ApiUser($translator, $userRole, $acl, $apiUserMapper, $apiTokenForm);
                },
                'user_service_email' => function ($sm) {
                    $translator = $sm->get('translator');
                    $renderer = $sm->get('ViewRenderer');
                    $transport = $sm->get('user_mail_transport');
                    $emailConfig = $sm->get('config')['email'];
                    return new Email($translator, $renderer, $transport, $emailConfig);
                },
                ApiApp::class => ApiAppFactory::class,
                'user_auth_storage' => function ($sm) {
                    $request = $sm->get('Request');
                    $response = $sm->get('Response');
                    $config = $sm->get('config');
                    return new Authentication\Storage\Session(
                        $request,
                        $response,
                        $config
                    );
                },
                'user_bcrypt' => function ($sm) {
                    $bcrypt = new Bcrypt();
                    $config = $sm->get('config');
                    $bcrypt->setCost($config['bcrypt_cost']);
                    return $bcrypt;
                },

                'user_hydrator' => function ($sm) {
                    return new DoctrineObject(
                        $sm->get('user_doctrine_em')
                    );
                },
                'user_form_activate' => function ($sm) {
                    return new Activate(
                        $sm->get('translator')
                    );
                },
                'user_form_register' => function ($sm) {
                    return new Register(
                        $sm->get('translator')
                    );
                },
                'user_form_login' => function ($sm) {
                    return new Login(
                        $sm->get('translator')
                    );
                },
                'user_form_password' => function ($sm) {
                    return new Password(
                        $sm->get('translator')
                    );
                },
                'user_form_passwordactivate' => function ($sm) {
                    return new Activate(
                        $sm->get('translator')
                    );
                },
                'user_form_apitoken' => function ($sm) {
                    $form = new ApiToken(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('user_hydrator'));
                    return $form;
                },

                'user_mapper_user' => function ($sm) {
                    return new \User\Mapper\User(
                        $sm->get('user_doctrine_em')
                    );
                },
                'user_mapper_newuser' => function ($sm) {
                    return new NewUser(
                        $sm->get('user_doctrine_em')
                    );
                },
                'user_mapper_apiuser' => function ($sm) {
                    return new ApiUser(
                        $sm->get('user_doctrine_em')
                    );
                },
                'user_mapper_session' => function ($sm) {
                    return new Session(
                        $sm->get('user_doctrine_em')
                    );
                },
                'user_mapper_loginattempt' => function ($sm) {
                    return new LoginAttempt(
                        $sm->get('user_doctrine_em')
                    );
                },

                'user_mail_transport' => function ($sm) {
                    $config = $sm->get('config');
                    $config = $config['email'];
                    $class = '\Zend\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Zend\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));
                    return $transport;
                },
                'user_auth_adapter' => function ($sm) {
                    $adapter = new Mapper(
                        $sm->get('user_bcrypt'),
                        $sm->get('application_service_legacy'),
                        $sm->get('user_service_loginattempt')
                    );
                    $adapter->setMapper($sm->get('user_mapper_user'));
                    return $adapter;
                },
                'user_pin_auth_adapter' => function ($sm) {
                    $adapter = new PinMapper(
                        $sm->get('application_service_legacy'),
                        $sm->get('user_service_loginattempt')
                    );
                    $adapter->setMapper($sm->get('user_mapper_user'));
                    return $adapter;
                },
                'user_auth_service' => function ($sm) {
                    return new Authentication\AuthenticationService(
                        $sm->get('user_auth_storage'),
                        $sm->get('user_auth_adapter')
                    );
                },
                'user_pin_auth_service' => function ($sm) {
                    return new AuthenticationService(
                        $sm->get('user_auth_storage'),
                        $sm->get('user_pin_auth_adapter')
                    );
                },
                'user_remoteaddress' => function ($sm) {
                    $remote = new RemoteAddress();
                    $isProxied = $sm->get('config')['proxy']['enabled'];
                    $trustedProxies = $sm->get('config')['proxy']['ip_addresses'];
                    $proxyHeader = $sm->get('config')['proxy']['header'];

                    $remote->setUseProxy($isProxied)
                        ->setTrustedProxies($trustedProxies)
                        ->setProxyHeader($proxyHeader);

                    return $remote->getIpAddress();
                },
                'user_role' => function ($sm) {
                    $authService = $sm->get('user_auth_service');
                    if ($authService->hasIdentity()) {
                        return $authService->getIdentity();
                    }
                    $apiService = $sm->get('user_service_apiuser');
                    if ($apiService->hasIdentity()) {
                        return 'apiuser';
                    }
                    $range = $sm->get('config')['tue_range'];
                    if (strpos($sm->get('user_remoteaddress'), $range) === 0) {
                        return 'tueguest';
                    }
                    return 'guest';
                },
                'acl' => function ($sm) {
                    // initialize the ACL
                    $acl = new Acl();

                    /**
                     * Define all basic roles.
                     *
                     * - guest: everyone gets at least this access level
                     * - tueguest: guest from the TU/e
                     * - user: GEWIS-member
                     * - apiuser: Automated tool given access by an admin
                     * - admin: Defined administrators
                     * - photo_guest: Special role for non-members but friends of GEWIS nonetheless
                     */
                    $acl->addRole(new Role('guest'));
                    $acl->addRole(new Role('tueguest'), 'guest');
                    $acl->addRole(new Role('user'), 'tueguest');
                    $acl->addrole(new Role('apiuser'), 'guest');
                    $acl->addrole(new Role('sosuser'), 'apiuser');
                    $acl->addrole(new Role('active_member'), 'user');
                    $acl->addrole(new Role('company_admin'), 'active_member');
                    $acl->addRole(new Role('admin'));
                    $acl->addRole(new Role('photo_guest'), 'guest');

                    $user = $sm->get('user_role');

                    // add user to registry
                    if ($user instanceof User) {
                        $roles = $user->getRoleNames();
                        // if the user has no roles, add the 'user' role by default
                        if (empty($roles)) {
                            $roles = ['user'];
                        }

                        if (count($user->getMember()->getCurrentOrganInstallations()) > 0) {
                            $roles[] = 'active_member';
                        }

                        $acl->addRole($user, $roles);
                    }

                    // admins are allowed to do everything
                    $acl->allow('admin');

                    // board members also are admins
                    $acl->allow('user', null, null, new IsBoardMember());

                    // configure the user ACL
                    $acl->addResource(new Resource('apiuser'));
                    $acl->addResource(new Resource('user'));

                    $acl->allow('user', 'user', ['password_change']);
                    $acl->allow('photo_guest', 'user', ['password_change']);
                    $acl->allow('tueguest', 'user', 'pin_login');

                    // sosusers can't do anything
                    $acl->deny('sosuser');
                    return $acl;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'user_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            ],
            'shared' => [
                'user_role' => false
            ]
        ];
    }
}
