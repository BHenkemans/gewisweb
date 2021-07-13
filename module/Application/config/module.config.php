<?php

/**
 * Zend Framework (http://framework.zend.com/).
 *
 * @see      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 *
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

use Application\Controller\IndexController;
use Application\View\Helper\BootstrapElementError;
use Application\View\Helper\FeaturedCompanyPackage;
use Application\View\Helper\LocalisedTextElement;
use Doctrine\Common\Cache\MemcachedCache;
use Laminas\ServiceManager\ServiceLocatorInterface;

return [
    'router' => [
        'routes' => [
            'lang' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/lang/:lang/',
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller' => 'Index',
                        'action' => 'lang',
                        'lang' => 'nl',
                    ],
                ],
                'priority' => 100,
            ],
            'teapot' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/coffee',
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller' => 'Index',
                        'action' => 'teapot',
                    ],
                ],
                'priority' => 100,
            ],
        ],
    ],
    'service_manager' => [
        'abstract_factories' => [
            'Laminas\Cache\Service\StorageCacheAbstractServiceFactory',
            'Laminas\Log\LoggerAbstractServiceFactory',
        ],
        'aliases' => [
            'translator' => 'MvcTranslator',
        ],
        'factories' => [
            'Laminas\Session\Config\ConfigInterface' => 'Laminas\Session\Service\SessionConfigFactory',
            'doctrine.cache.my_memcached' => function () {
                $cache = new MemcachedCache();
                $memcached = new Memcached();
                $memcached->addServer('memcached', 11211);
                $cache->setMemcached($memcached);

                return $cache;
            },
        ],
    ],
    'translator' => [
        'locale' => 'nl',
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
            ],
            // Zend\Validate translation
            [
                'type' => 'phparray',
                'base_dir' => 'vendor/zendframework/zendframework/resources/languages/',
                'pattern' => '%s/Zend_Validate.php',
                'text_domain' => 'validate',
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            'Application\Controller\Index' => function () {
                return new IndexController();
            },
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => (APPLICATION_ENV === 'production' ? 'error/404' : 'error/debug/404'),
        'exception_template' => (APPLICATION_ENV === 'production' ? 'error/500' : 'error/debug/500'),
        'template_map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'application/index/teapot' => __DIR__ . '/../view/application/index/418.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/403' => __DIR__ . '/../view/error/403.phtml',
            'error/418' => __DIR__ . '/../view/error/418.phtml',
            'error/500' => __DIR__ . '/../view/error/500.phtml',
            'error/debug/404' => __DIR__ . '/../view/error/debug/404.phtml',
            'error/debug/403' => __DIR__ . '/../view/error/debug/403.phtml',
            'error/debug/500' => __DIR__ . '/../view/error/debug/500.phtml',
            'paginator/default' => __DIR__ . '/../view/partial/paginator.phtml',
        ],
        'template_path_stack' => [
            'laminas-developer-tools' => __DIR__ . '/../view',
            __DIR__ . '/../view',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'featuredCompanyPackage' => function (ServiceLocatorInterface $sm) {
                $companyService = $sm->get('company_service_company');

                return new FeaturedCompanyPackage($companyService);
            },
            'bootstrapElementError' => function () {
                return new BootstrapElementError();
            },
            'localisedTextElement' => function () {
                return new LocalisedTextElement();
            },
        ],
    ],
];
