<?php

namespace Decision\Form;

use Zend\Form\Form;
use Zend\Mvc\I18n\Translator;

class ReorderDocument extends Form
{
    /**
     * @var Translator
     */
    protected $translator;

    public function __construct($name = null, $options = array()) {
        parent::__construct($name, $options);

        $this->setAttribute('method', 'post');
    }

    public function setupElements()
    {
        $this->add([
            'type'    => \Zend\Form\Element\Radio::class,
            'name'    => 'direction',
            'options' => [
                'label'            => 'Label',
                'value_options'    => [
                    'up'   => $this->generateIcon('glyphicon-chevron-up', $this->translator->translate('Move up')),
                    'down' => $this->generateIcon('glyphicon-chevron-down', $this->translator->translate('Move down')),
                ],
                'label_attributes' => [
                    'class' => 'label label-radio-hidden'
                ],
                'label_options'    => [
                    'disable_html_escape' => true, // Required to render HTML icons
                ],
            ]
        ]);

        $this->add([
            'type'       => \Zend\Form\Element\Hidden::class,
            'name'       => 'document',
            'attributes' => [
                'value' => null, // Value should be populated in the view
            ]
        ]);

        return $this;
    }

    public function setTranslator(Translator $translator) {
        $this->translator = $translator;

        return $this;
    }

    /**
     * Returns an icon as a HTML string
     *
     * FUTURE: Think of a better way to show icons in the label. Icons are
     * layout and shouldn't be defined in the Form.
     *
     * @param string $className Glyphicon class
     * @param string $title Element title
     * @return string
     */
    private function generateIcon($className, $title)
    {
        return "<span class=\"glyphicon {$className}\" title=\"{$title}\"></span>";
    }
}