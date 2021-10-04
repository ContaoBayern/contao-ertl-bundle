<?php

declare(strict_types=1);

use Contaobayern\ErtlBundle\Model\MemberTokenModel;

$GLOBALS['TL_MODELS']['tl_member_token'] = MemberTokenModel::class;

// Add notification type
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['ertl'] = [
    'ertl_formpost'   => [
        'recipients'    => [
            'form_*',
        ],
        'email_subject' => [
            'domain'
        ],
        'email_text' =>[
            'form_*',
            'domain',
            'loginlink'
        ],
        'email_html' => [
            'form_*',
            'domain',
            'loginlink'
        ],
        'email_sender_name' => [],
        'email_sender_address' => ['admin_email'],
        'email_sender_recipient_cc' => [],
        'email_sender_recipient_bcc' => [],
        'email_replyTo' => []
    ]
];

// Add anotification type for errors
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['ertl']['ertl_formpost_member_error'] =
    $GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['ertl']['ertl_formpost'];
foreach (['email_text', 'email_html'] as $key) {
    unset($GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['ertl']['ertl_formpost_member_error'][$key]['loginlink']);
}

$GLOBALS['BE_MOD']['accounts']['ertl.membertoken'] = ['tables' => ['tl_member_token']];



