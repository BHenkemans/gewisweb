<?php

namespace Education\Form;

use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\NotEmpty;

class SearchCourse extends Form
{
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'query',
                'type' => 'text',
                'options' => [
                    'label' => $translate->translate('Search query'),
                ],
            ]
        );

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add(
            [
                'name' => 'query',
                'required' => true,
                'validators' => [
                    ['name' => NotEmpty::class],
                ],
            ]
        );

        $this->setInputFilter($filter);
    }
}