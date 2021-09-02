<?php

namespace Contaobayern\ErtlBundle\EventListener;

use Contao\Form;
use Contao\Widget;
use Doctrine\DBAL\Connection;

class ValidateFormFieldListener
{
    // TODO: refactor to have these entries in a database table that can be managed from within the Contao back end
    const INVALID_DOMAINS = [
        't-online.de',
        'gmx.de', 'gmx.com',
        'google.com',
        'web.de'
    ];

    // TODO: use $connection to query (currently hard coded) INVALID_DOMAINS from the database
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * The validateFormField hook is triggered when a form field is submitted.
     */
    public function onValidateFormField(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if ('email' === $widget->name) {
            if ($this->isInvalidDomain($widget->value)) {
                $widget->addError('Bitte verwenden Sie eine geschäftliche E-Mail-Adresse.'); // TODO aus Sprachvariable
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
        return in_array($this->getDomain($email), self::INVALID_DOMAINS);
    }
}

