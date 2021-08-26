<?php

namespace Decision\Form;

use Decision\Mapper\Meeting as MeetingMapper;
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\MimeType;

class Notes extends Form implements InputFilterProviderInterface
{
    public const ERROR_FILE_EXISTS = 'file_exists';

    protected $translator;

    public function __construct(Translator $translator, MeetingMapper $mapper)
    {
        parent::__construct();
        $this->translator = $translator;

        $options = [];
        foreach ($mapper->findAllMeetings() as $meeting) {
            $meeting = $meeting[0];
            $name = $meeting->getType() . '/' . $meeting->getNumber();
            $options[$name] = $meeting->getType() . ' ' . $meeting->getNumber()
                . ' (' . $meeting->getDate()->format('Y-m-d') . ')';
        }

        $this->add(
            [
                'name' => 'meeting',
                'type' => 'select',
                'options' => [
                    'label' => $translator->translate('Meeting'),
                    'empty_option' => $translator->translate('Choose a meeting'),
                    'value_options' => $options,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'upload',
                'type' => 'file',
                'option' => [
                    'label' => $translator->translate('Notes to upload'),
                ],
            ]
        );
        $this->get('upload')->setLabel($translator->translate('Notes to upload'));

        $this->add(
            [
                'name' => 'submit',
                'type' => 'submit',
                'attributes' => [
                    'value' => $translator->translate('Submit'),
                ],
            ]
        );
    }

    /**
     * Set an error.
     *
     * @param string $error
     */
    public function setError($error)
    {
        if (self::ERROR_FILE_EXISTS == $error) {
            $this->setMessages(
                [
                    'meeting' => [
                        $this->translator->translate('There already are notes for this meeting'),
                    ],
                ]
            );
        }
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification()
    {
        return [
            'upload' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Extension::class,
                        'options' => [
                            'extension' => 'pdf',
                        ],
                    ],
                    [
                        'name' => MimeType::class,
                        'options' => [
                            'mimeType' => 'application/pdf',
                        ],
                    ],
                ],
            ],
        ];
    }
}
