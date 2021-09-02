<?php

declare(strict_types=1);

namespace Contaobayern\ErtlBundle\Model;

use Contao\MemberModel;
use Contao\Model;

/**
 * @property int    $id
 * @property int    $tstamp
 * @property string $token
 * @property string $domain
 * @property int    $pid
 * @property int    $validuntil
 *
 * @author Andreas Fieger
 */
class MemberTokenModel extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_member_token';

    public static function findByMemberId(int $id): ?MemberTokenModel
    {
        return self::findOneBy(['pid=?'], [$id]);
    }
}
