<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Model;

use Carbon\Carbon;

/**
 * @property int $idMember
 * @property int $idUnit
 * @property int $idRank
 * @property int $weight
 * @property string $name
 * @property ?Carbon $dateJoined
 * @property ?Carbon $dateLastPromotion
 * @property ?string $remarks
 * @property ?string $remarksInternal
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 *
 * @property Unit $unit
 * @property Rank $rank
 */
class Member extends \Orbat\Model
{
    public static function tablename()
    {
        return 'members';
    }

    public static function primarykey()
    {
        return 'idMember';
    }

    public function relations()
    {
        return [
            'unit' => [BELONGS_TO, "Orbat\Model\Unit", 'idUnit'],
            'rank' => [BELONGS_TO, "Orbat\Model\Rank", 'idRank'],
        ];
    }

}