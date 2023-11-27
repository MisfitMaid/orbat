<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Model;

use Carbon\Carbon;

/**
 * @property int $idGroup
 * @property int $idUnit
 * @property ?int $idParent
 * @property int $weight
 * @property string $name
 * @property ?string $color
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 *
 * @property Unit $unit
 * @property ?self $parent
 * @property self[] $childs
 */
class Group extends \Orbat\Model
{
    public static function tablename()
    {
        return 'squads'; // "groups" is an sql reserved word
    }

    public static function primarykey()
    {
        return 'idGroup';
    }

    public function relations()
    {
        return [
            'unit' => [BELONGS_TO, "Orbat\Model\Unit", 'idUnit'],
            'parent' => [BELONGS_TO, self::class, 'idParent'],
            'childs' => [HAS_MANY, self::class, 'idParent', ['order' => 'asc', 'orderby' => 'weight']],
        ];
    }

    public function depth(): int
    {
        if (!$this->parent) {
            return 0;
        } else {
            return $this->parent->depth() + 1;
        }
    }

}