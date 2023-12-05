<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Model;

use Carbon\Carbon;
use Orbat\Snowflake;

/**
 * @property int $idUnit
 * @property string $name
 * @property ?string $icon
 * @property ?string $slug
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 *
 * @property UnitEditor[] $editors
 * @property Member[] $members
 * @property Rank[] $ranks
 * @property Medal[] $medals
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
            'medals' => [HAS_MANY, "Orbat\Model\Medal", 'idUnit', ['order' => 'asc', 'orderby' => 'weight']],
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

    public function slug(): string
    {
        if (isset($this->slug) && is_string($this->slug) && mb_strlen(trim($this->slug)) > 0) {
            return $this->slug;
        }
        return Snowflake::format($this->idUnit);
    }

    public function remove()
    {
        foreach ($this->editors as $ed) {
            $ed->remove();
        }
        foreach ($this->members as $m) {
            $m->remove();
        }
        foreach ($this->ranks as $r) {
            $r->remove();
        }
        foreach ($this->endorsements as $end) {
            $end->remove();
        }
        foreach ($this->groups as $g) {
            $g->remove();
        }
        foreach ($this->medals as $med) {
            $med->remove();
        }

        return parent::remove();
    }

}