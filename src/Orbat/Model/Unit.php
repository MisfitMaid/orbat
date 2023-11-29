<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Model;

use Carbon\Carbon;

/**
 * @property int $idUnit
 * @property string $name
 * @property ?string $icon
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 *
 * @property UnitEditor[] $editors
 * @property Member[] $members
 * @property Rank[] $ranks
 * @property Endorsement[] $endorsements
 * @property Group[] $groups
 */
class Unit extends \Orbat\Model
{
    public static function tablename()
    {
        return 'units';
    }

    public static function primarykey()
    {
        return 'idUnit';
    }

    public function relations()
    {
        return [
            'editors' => [HAS_MANY, "Orbat\Model\UnitEditor", 'idUnit'],
            'members' => [HAS_MANY, "Orbat\Model\Member", 'idUnit'],
            'ranks' => [HAS_MANY, "Orbat\Model\Rank", 'idUnit', ['order' => 'asc', 'orderby' => 'weight']],
            'endorsements' => [HAS_MANY, "Orbat\Model\Endorsement", 'idUnit', ['order' => 'asc', 'orderby' => 'weight']],
            'groups' => [HAS_MANY, "Orbat\Model\Group", 'idUnit', ['order' => 'asc', 'orderby' => 'weight']],
        ];
    }

    /**
     * @return Group[]
     */
    public function groupTree(): array
    {
        $tree = [];
        foreach ($this->groups as $group) {
            if (!$group->parent) {
                $tree[] = $group;
            }
        }
        return $tree;
    }

    /**
     * @return Member[]
     */
    public function membersSorted(): array
    {
        $mems = $this->members;
        usort($mems, ["\\Orbat\\Model\\Member", "memberSortFunc"]);
        return $mems;
    }

}