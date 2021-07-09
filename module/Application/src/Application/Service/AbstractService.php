<?php

namespace Application\Service;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;

abstract class AbstractService implements ServiceManagerAwareInterface
{
    /**
     * Service manager.
     *
     * @var ServiceManager
     */
    protected $sm;

    /**
     * Get the translator.
     *
     * @return Zend\Mvc\I18n\Translator
     */
    public function getTranslator()
    {
        // TODO: This is not a nice method from a design perspective.
        // Ideally we explicitly pass the translator to any other entity that needs it.
        // Then $translator can be a protected property
        return $this->getServiceManager()->get('translator');
    }

    /**
     * Set the service manager.
     *
     * @param ServiceManager $sm
     */
    public function setServiceManager(ServiceManager $sm)
    {
        $this->sm = $sm;
    }

    /**
     * Get the service manager.
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->sm;
    }
}
