<?php

namespace Activity\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

// use Laminas\Hydrator\ClassMethodsHydrator;

class ActivityCategory extends Form implements InputFilterProviderInterface
{
    /**
     * @var Translator
     */
    protected $translator;

    public function __construct(Translator $translator)
    {
        parent::__construct('category');
        $this->translator = $translator;

        $this->add(
            [
                'name' => 'language_dutch',
                'type' => 'Laminas\Form\Element\Checkbox',
                'options' => [
                    'checked_value' => 1,
                    'unchecked_value' => 0,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'language_english',
                'type' => 'Laminas\Form\Element\Checkbox',
                'options' => [
                    'checked_value' => 1,
                    'unchecked_value' => 0,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'name',
                'attributes' => [
                    'type' => 'text',
                ],
                'options' => [
                    'label' => $translator->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'nameEn',
                'attributes' => [
                    'type' => 'text',
                ],
                'options' => [
                    'label' => $translator->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'attributes' => [
                    'type' => 'submit',
                    'value' => 'Create',
                ],
            ]
        );
    }

    /**
     * Get the input filter.
     *
     * @return array
     */
    public function getInputFilterSpecification()
    {
        $filter = [];

        if (
            isset($this->data['language_english'])
            && $this->data['language_english']
        ) {
            $filter += $this->inputFilterGeneric('En');
        }

        if (
            isset($this->data['language_dutch'])
            && $this->data['language_dutch']
        ) {
            $filter += $this->inputFilterGeneric('');
        }

        // At least one the two languages needs to be set. If neither is set
        // display a message at both, indicating that they need to be set.
        if (
            (isset($this->data['language_dutch']) && !$this->data['language_dutch'])
            && (isset($this->data['language_english']) && !$this->data['language_english'])
        ) {
            unset($this->data['language_dutch'], $this->data['language_english']);

            $filter += [
                'language_dutch' => [
                    'required' => true,
                ],
                'language_english' => [
                    'required' => true,
                ],
            ];
        }

        return $filter;
    }

    /**
     * Build a generic input filter.
     *
     * @input string $languagePostFix Postfix that is used for language fields to indicate that a field belongs to that
     * language
     *
     * @return array
     */
    protected function inputFilterGeneric($languagePostFix)
    {
        return [
            'name' . $languagePostFix => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Validate the form.
     *
     * @return bool
     */
    public function isValid()
    {
        $valid = parent::isValid();
        $this->isValid = $valid;

        return $valid;
    }
}