<?php

namespace Contaobayern\ErtlBundle\Helper;

use Contao\Controller;
use Contao\Idna;
use Contao\Environment;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\System;
use Contaobayern\ErtlBundle\EventListener\ProcessFormDataListener;
use Contaobayern\ErtlBundle\Model\MemberTokenModel;
use NotificationCenter\Model\Notification;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;


class MemberLoginManager
{
    const REQUIRED_MEMBER_FIELDS_WE_HANDLE = ['tstamp', 'firstname', 'lastname', 'email', 'username', 'password'];
    const REDIRECT_PAGE_FIELD = 'redirecttopagewithid';

    /** @var EncoderFactoryInterface */
    private $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function createMemberIfNotExists(array $data, string $domain): MemberModel
    {
        $member = MemberModel::findByEmail($data['email']);

        if (null === $member) {
            $encoder = $this->encoderFactory->getEncoder(FrontendUser::class);
            $plainPassword = Uuid::uuid4()->getHex();
            $hash = $encoder->encodePassword($plainPassword, null);

            $member = new MemberModel();
            $member->tstamp = time();
            $member->login = true;
            $member->password = $hash;
            $member->dateAdded = time();

            $groups = [];
            $container = System::getContainer();
            $parameterName = sprintf('ertl_assign_groups.%s', $domain);
            if ($container->hasParameter($parameterName)) {
                $groups = $container->getParameter($parameterName);
            }
            $member->groups = $groups;

            // 'email' is a required form field (@see ProcessFormDataListener::FORM_REQUIRED_FIELDS)
            $member->email = $data[ProcessFormDataListener::FORM_FIELD_EMAIL];
            $member->username = $data[ProcessFormDataListener::FORM_FIELD_EMAIL];
            // Fields that might be available in the form
            // (a) set defaults for required tl_member fields (which were not already set above)
            $member->firstname = empty($data['firstname']) ? 'Kein Vorname' : $data['firstname'];
            $member->lastname = empty($data['lastname']) ? 'Kein Nachname' : $data['lastname'];
            // (b) set optional tl_member fields from form data
            Controller::loadDataContainer('tl_member');
            foreach (array_keys($GLOBALS['TL_DCA']['tl_member']['fields']) as $key) {
                if (in_array($key, self::REQUIRED_MEMBER_FIELDS_WE_HANDLE)) {
                    // should already be handled above
                    continue;
                } else {
                    // this should not happen (but an extension might have added more required fields)
                    if ($GLOBALS['TL_DCA']['tl_member']['fields'][$key]['eval']['mandatory']) {
                        System::log("Verpflichtendes tl_member Field '$key' nicht im ER+TL Formular vorhanden", __METHOD__, TL_ERROR);
                    }
                }
                // we only handle scalar fields
                if (in_array($GLOBALS['TL_DCA']['tl_member']['fields'][$key]['inputType'], ['text', 'select'])) {
                    // Do not let the user fiddle with the tl_member fileds we want to control -- e.g. by submitting a field named 'groups'
                    if (in_array($key, ['groups', 'tstamp', 'password', 'dateAdded'])) {
                        continue;
                    }
                    $member->$key = $data[$key];
                }
            }

            $member->save();
        }

        return $member;
    }

    public function createTokenForMember(array $data, string $domain): ?string
    {
        $member = MemberModel::findByEmail($data['email']);
        if (null === $member) {
            return null;
        }

        $token = MemberTokenModel::findByMemberId($member->id);
        if (null === $token) {
            $token = new MemberTokenModel();
            $token->pid = $member->id;
            $token->token = Uuid::uuid4()->getHex();
            $token->domain = $domain;
            $token->validuntil = time() + 7 * 24 * 60 * 60; // TODO: Dauer konfigurierbar machen
        }
        $token->tstamp = time();

        $token->save();

        return $token->token;
    }

    public function sendNotifications(string $notificationType, array $data, string $token = ''): void
    {
        $redirectPageParameter = '';
        if (isset($data[self::REDIRECT_PAGE_FIELD])) {
            if (is_numeric($data[self::REDIRECT_PAGE_FIELD])) {
                $redirectPageParameter = '/'.$data[self::REDIRECT_PAGE_FIELD];
            }
        }

        // Daten für die Notifications (Simple Tokens)
        $notificationData = [
            'admin_email' => $GLOBALS['TL_ADMIN_EMAIL'],
            'domain'      => Idna::decode(Environment::get('host')),
            'loginlink'   => '' === $token
                ? ''
                : sprintf('%s/_login/%s%s',
                    Idna::decode(Environment::get('host')),
                    $token,
                    $redirectPageParameter
                ),
        ];

        // Daten aus dem Formular hinzufügen
        foreach ($data as $key => $value) {
            $notificationData['form_' . $key] = $value;
        }

        $objNotificationCollection = Notification::findByType($notificationType);
        if (null !== $objNotificationCollection) {
            while ($objNotificationCollection->next()) {
                $objNotification = $objNotificationCollection->current();
                $objNotification->send($notificationData);
            }
        }
    }

}
