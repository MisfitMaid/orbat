<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Model;

use Carbon\Carbon;

/**
 * @property int $idMedal
 * @property int $idUnit
 * @property int $weight
 * @property string $name
 * @property string $image
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 *
 * @property Unit $unit
 * @property MemberMedal[] $recipients
 */
class Medal extends \Orbat\Model
{
    public static function tablename()
    {
        return 'medals';
    }

    public static function primarykey()
    {
        return 'idMedal';
    }

    public function relations()
    {
        return [
            'unit' => [BELONGS_TO, "Orbat\Model\Unit", 'idUnit'],
            'recipients' => [HAS_MANY, "Orbat\Model\MemberMedal", 'idMedal']
        ];
    }

}