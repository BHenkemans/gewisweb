<?php

namespace Photo\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\Translator;

class CreateAlbum extends Form {

    public function __construct(Translator $translate) {
        parent::__construct();

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => $translate->translate('Album title')
            )
        ));

        $this->add(array(
            'name' => 'Author',
            'type' => 'text',
            'options' => array(
                'label' => $translate->translate('Author')
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translate->translate('Create album')
            )
        ));

        $this->initFilters();
    }

    protected function initFilters() {
        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'query',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty'),
                array('name' => 'alnum',
                    'options' => array(
                        'allowWhiteSpace' => true
                    )
                )
            )
        ));
        $this->setInputFilter($filter);
    }

}
