<?php

namespace Contaobayern\ErtlBundle\EventListener;

use Contao\Form;
use Contao\System;
use Contao\Widget;

class ValidateFormFieldListener
{
    /**
     * @var array
     */
    protected $invalid_domains = [];

    public function __construct()
    {
        $container = System::getContainer();
        if ($container->hasParameter('ertl_rejected_domains')) {
            $ertl_rejected_domains = $container->getParameter('ertl_rejected_domains');
            if (is_array($ertl_rejected_domains)) {
                $this->invalid_domains = $ertl_rejected_domains;
            }
        }
    }

    /**
     * The validateFormField hook is triggered when a form field is submitted.
     */
    public function onValidateFormField(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if ('email' === $widget->name) {
            if ($this->isInvalidDomain($widget->value)) {
                $widget->addError($GLOBALS['TL_LANG']['ERR']['invalidEmailDomain']);
            }
        }

        return $widget;
    }

    protected function getDomain($email): string
    {
        if (preg_match('/[^@]+@(.+)/', $email, $matches)) {
            return $matches[1];
        }

        return '';
    }

    protected function isInvalidDomain($email): bool
    {
        return in_array($this->getDomain($email), $this->invalid_domains);
    }
}

