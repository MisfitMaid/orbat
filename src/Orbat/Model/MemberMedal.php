<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Model;

use Carbon\Carbon;

/**
 * @property int $idMemberMedal
 * @property int $idMember
 * @property int $idMedal
 * @property string $remarks
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 *
 * @property Member $member
 * @property Medal $medal
 */
class MemberMedal extends \Orbat\Model
{
    public static function tablename()
    {
        return 'members_medals';
    }

    public static function primarykey()
    {
        return 'idMemberMedal';
    }

    public function relations()
    {
        return [
            'member' => [BELONGS_TO, "Orbat\Model\Member", 'idMember'],
            'medal' => [BELONGS_TO, "Orbat\Model\Medal", 'idMedal']
        ];
    }

}