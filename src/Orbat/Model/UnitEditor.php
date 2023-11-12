<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Model;

use Carbon\Carbon;

/**
 * @property int $idUnitEditor
 * @property int $idUnit
 * @property int $idUser
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 *
 * @property Unit $unit
 * @property User $user
 */
class UnitEditor extends \Orbat\Model
{
    public static function tablename()
    {
        return 'units_editors';
    }

    public static function primarykey()
    {
        return 'idUnitEditor';
    }

    public function relations()
    {
        return [
            'unit' => [BELONGS_TO, "Orbat\Model\Unit", 'idUnit'],
            'user' => [BELONGS_TO, "Orbat\Model\User", 'idUser']
        ];
    }

}