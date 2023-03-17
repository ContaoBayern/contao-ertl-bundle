<?php

namespace Contaobayern\ErtlBundle\Cron;

use Contao\MemberModel;
use Contao\System;
use Contaobayern\ErtlBundle\Model\MemberTokenModel;

class PurgeTokens
{

    public function run(): void
    {
        //print "Cron PurgeTokens\n";
        $members = MemberModel::findAll();
        foreach ($members as $member) {
            $tokens = MemberTokenModel::findBy('pid', $member->id) ?? [];
            // print_r(['member'=>$member->username, 'tokens'=>$tokens]);
            /** @var MemberTokenModel $token */
            foreach ($tokens as $token) {
                //printf("%s [%s]\n", $token->id, $member->username);
                // Nur zum Test außerhalb von if ($token->validuntil < time())
                $parameterName = sprintf('ertl_assign_groups.%s', $token->domain);
                $container = System::getContainer();
                if ($container->hasParameter($parameterName)) {
                    $groups = $container->getParameter($parameterName);
                }
                //print_r(['to_remove'=>$groups]);
                if ($token->validuntil < time()) {
                    //printf("Token %s has expired. TODO: delete and cleanup member's [%s] groups\nIf Member has no groups: delete Member",
                    //    $token->id,
                    //    $member->username
                    //);
                }
            }
        }
    }
}
