<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Model;

use Carbon\Carbon;

/**
 * @property int $idMemberEndorsement
 * @property int $idMember
 * @property int $idEndorsement
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 *
 * @property Member $member
 * @property Endorsement $endorsement
 */
class MemberEndorsement extends \Orbat\Model
{
    public static function tablename()
    {
        return 'members_endorsements';
    }

    public static function primarykey()
    {
        return 'idMemberEndorsement';
    }

    public function relations()
    {
        return [
            'member' => [BELONGS_TO, "Orbat\Model\Member", 'idMember'],
            'endorsement' => [BELONGS_TO, "Orbat\Model\Endorsement", 'idEndorsement']
        ];
    }

}