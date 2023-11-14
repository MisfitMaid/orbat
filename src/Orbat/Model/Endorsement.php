<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Model;

use Carbon\Carbon;

/**
 * @property int $idEndorsement
 * @property int $idUnit
 * @property int $weight
 * @property string $abbr
 * @property string $name
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 *
 * @property Unit $unit
 */
class Endorsement extends \Orbat\Model
{
    public static function tablename()
    {
        return 'endorsements';
    }

    public static function primarykey()
    {
        return 'idEndorsement';
    }

    public function relations()
    {
        return [
            'unit' => [BELONGS_TO, "Orbat\Model\Unit", 'idUnit'],
        ];
    }

}