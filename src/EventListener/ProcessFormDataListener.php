<?php

namespace Contaobayern\ErtlBundle\EventListener;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\Environment;
use Contao\Form;
use Contao\System;
use Contaobayern\ErtlBundle\Helper\MemberLoginManager;

class ProcessFormDataListener
{
    const FORM_FIELD_EMAIL = 'email';
    const FORM_FIELD_IDENTIFICATION = 'ertl_login';
    const FORM_VALUE_IDENTIFICATION = '9uetwrg7K83z7';
    const FORM_REQUIRED_FIELDS = [self::FORM_FIELD_EMAIL, self::FORM_FIELD_IDENTIFICATION];

    /**
     * The processFormData hook is triggered after a form has been submitted.
     */
    public function onProcessFormData(array $submittedData, array $formData, ?array $files, array $labels, Form $form): void
    {
        if (!$this->isRelevantForm($submittedData)) {
            return;
        }
        if (!$this->requiredFieldsAreAvailable($submittedData)) {
            return;
        }

        /** @var MemberLoginManager $manager */
        $manager = System::getContainer()->get('contaobayern.ertl.helper.member_login_manager');
        $member = $manager->createMemberIfNotExists($submittedData, Environment::get('host'));
        if ($member->disable) {
            // Member already existed but was deactivated => send notification
            $manager->sendNotifications('ertl_formpost_member_error', $submittedData);
            // TODO (?): or should this rather be an error page redirect (or even both)
            // throw new RedirectResponseException('fehlerseite.html'); // with a configurable alias of course ;-)
            return;
        }
        $token = $manager->createTokenForMemberIfNotExists($submittedData, Environment::get('host'));
        $manager->sendNotifications('ertl_formpost', $submittedData, $token);
    }

    protected function isRelevantForm(array $formData): bool
    {
        return isset($formData[self::FORM_FIELD_IDENTIFICATION]) && $formData[self::FORM_FIELD_IDENTIFICATION] === self::FORM_VALUE_IDENTIFICATION;
    }

    protected function requiredFieldsAreAvailable(array $formData): bool
    {
        foreach (self::FORM_REQUIRED_FIELDS as $key) {
            if (!isset($formData[$key])) {
                return false;
            }
        }
        return true;
    }
}
