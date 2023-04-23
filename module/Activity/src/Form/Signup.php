<?php

declare(strict_types=1);

namespace Activity\Form;

use Activity\Model\{
    SignupField as SignupFieldModel,
    SignupList as SignupListModel,
};
use Laminas\Captcha\Image as ImageCaptcha;
use Laminas\Form\Element\{
    Captcha,
    Csrf,
    Email,
    Number,
    Radio,
    Select,
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{
    EmailAddress,
    StringLength,
};

class Signup extends Form implements InputFilterProviderInterface
{
    public const USER = 1;
    public const EXTERNAL_USER = 2;
    public const EXTERNAL_ADMIN = 3;

    protected int $type;

    protected SignupListModel $signupList;

    public function __construct()
    {
        parent::__construct('activitysignup');
        $this->setAttribute('method', 'post');

        $this->add(
            [
                'name' => 'security',
                'type' => Csrf::class,
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => 'Subscribe',
                ],
            ]
        );
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param SignupListModel $signupList
     */
    public function initialiseExternalForm(SignupListModel $signupList): void
    {
        $this->add(
            [
                'name' => 'captcha',
                'type' => Captcha::class,
                'options' => [
                    'captcha' => new ImageCaptcha(
                        [
                            'font' => 'public/fonts/bitstream-vera/Vera.ttf',
                            'imgDir' => 'public/img/captcha/',
                            'imgUrl' => '/img/captcha/',
                        ]
                    ),
                ],
            ]
        );

        $this->initialiseExternalAdminForm($signupList);
        $this->type = Signup::EXTERNAL_USER;
    }

    /**
     * Initialize the form for external subscriptions by admin, i.e. set the language and the fields
     * Add every field in $signupList to the form.
     *
     * @param SignupListModel $signupList
     */
    public function initialiseExternalAdminForm(SignupListModel $signupList): void
    {
        $this->add(
            [
                'name' => 'fullName',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'email',
                'type' => Email::class,
            ]
        );

        $this->initialiseForm($signupList);
        $this->type = Signup::EXTERNAL_ADMIN;
    }

    /**
     * Initialize the form, i.e. set the language and the fields
     * Add every field in $signupList to the form.
     *
     * @param SignupListModel $signupList
     */
    public function initialiseForm(SignupListModel $signupList): void
    {
        foreach ($signupList->getFields() as $field) {
            $this->add($this->createSignupFieldElementArray($field));
        }

        $this->signupList = $signupList;
        $this->type = Signup::USER;
    }

    /**
     * Creates an array of the form element specification for the given $field,
     * to be used by the factory.
     *
     * @param SignupFieldModel $field
     *
     * @return array
     */
    protected function createSignupFieldElementArray(SignupFieldModel $field): array
    {
        $result = [
            'name' => strval($field->getId()),
        ];

        switch ($field->getType()) {
            case 0: //'Text'
                $result['type'] = 'Text';
                break;
            case 1: //'Yes/No'
                $result['type'] = Radio::class;
                $result['options'] = [
                    'value_options' => [
                        '1' => 'Yes',
                        '0' => 'No',
                    ],
                ];
                break;
            case 2: //'Number'
                $result['type'] = Number::class;
                $result['attributes'] = [
                    'min' => $field->getMinimumValue(),
                    'max' => $field->getMaximumValue(),
                    'step' => '1',
                ];
                break;
            case 3: //'Choice'
                $values = [];
                foreach ($field->getOptions() as $option) {
                    $values[$option->getId()] = $option->getValue()->getText();
                }
                $result['type'] = Select::class;
                $result['options'] = [
                    //'empty_option' => 'Make a choice',
                    'value_options' => $values,
                ];
                break;
        }

        return $result;
    }

    /**
     * Apparently, validators are automatically added, so this works.
     *
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        $filter = [];
        if (
            Signup::EXTERNAL_USER === $this->type ||
            Signup::EXTERNAL_ADMIN === $this->type
        ) {
            $filter['fullName'] = [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ];
            $filter['email'] = [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                    [
                        'name' => EmailAddress::class,
                    ],
                ],
            ];
        }

        return $filter;
    }
}
