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
 * @property Member[] $members
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
            'members' => [HAS_MANY, "Orbat\Model\Member", 'idGroup'],
        ];
    }

    /**
     * @return Member[]
     */
    public function membersSorted(): array
    {
        $mems = $this->members;
        usort($mems, function (Member $a, Member $b) {
            $rank = $b->rank->weight <=> $a->rank->weight;
            if ($rank == 0) {
                return $b->dateJoined <=> $a->dateJoined;
            }
            return $rank;
        });
        return $mems;
    }

    public function depth(): int
    {
        if (!$this->parent) {
            return 0;
        } else {
            return $this->parent->depth() + 1;
        }
    }

    public function getColorPair(): string
    {
        $r = hexdec(substr($this->color, 1, 2)) / 0xff;
        $g = hexdec(substr($this->color, 3, 2)) / 0xff;
        $b = hexdec(substr($this->color, 5, 2)) / 0xff;
        $luma = 0.299 * $r + 0.587 * $g + 0.114 * $b;
        if ($luma > 0.5) {
            return "#000";
        } else {
            return "#fff";
        }
    }

}