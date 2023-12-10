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
 * @property int $idMember
 * @property int $idUnit
 * @property int $idRank
 * @property int $idGroup
 * @property string $name
 * @property string $playerName
 * @property string $role
 * @property ?Carbon $dateJoined
 * @property ?Carbon $dateLastPromotion
 * @property ?string $remarks
 * @property ?string $remarksInternal
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 *
 * @property Unit $unit
 * @property Rank $rank
 * @property Group $group
 * @property MemberEndorsement[] $endorsements
 * @property MemberMedal[] $medals
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

    public static function memberSortFunc(Member $a, Member $b): int
    {
        $rank = $b->rank->weight <=> $a->rank->weight;
        if ($rank == 0) {
            return $a->dateJoined <=> $b->dateJoined;
        }
        return $rank;
    }

    public function relations()
    {
        return [
            'unit' => [BELONGS_TO, "Orbat\Model\Unit", 'idUnit'],
            'rank' => [BELONGS_TO, "Orbat\Model\Rank", 'idRank'],
            'group' => [BELONGS_TO, "Orbat\Model\Group", 'idGroup'],
            'endorsements' => [HAS_MANY, "Orbat\Model\MemberEndorsement", 'idMember'],
            'medals' => [HAS_MANY, "Orbat\Model\MemberMedal", 'idMember'],
        ];
    }

    public function beforeSave()
    {
        parent::beforeSave();

        $this->dateJoined = $this->dateJoined->toDateString();
        $this->dateLastPromotion = $this->dateLastPromotion->toDateString();
    }

    public function afterSave()
    {
        parent::afterSave();

        $this->dateJoined = new Carbon($this->dateJoined);
        $this->dateLastPromotion = new Carbon($this->dateLastPromotion);
    }

    public function getServiceID(): string
    {
        $nameparts = explode(" ", $this->name);
        if (count($nameparts) < 2) {
            $initials = $nameparts[0][0];
        } else {
            $initials = $nameparts[0][0] . $nameparts[1][0];
        }

        $numhash = hash("crc32", $this->dateJoined->timestamp . $this->name);

        return sprintf("%s-%s-%s",
            Snowflake::format($this->idMember),
            $numhash,
            mb_strtoupper($initials)
        );
    }

    public function remove(): bool
    {
        foreach ($this->endorsements as $me) {
            $me->remove();
        }
        foreach ($this->medals as $me) {
            $me->remove();
        }
        return parent::remove();
    }

    public function remarksMD(): string
    {
        global $mdParser;
        return $mdParser->text($this->remarks);
    }

    public function remarksInternalMD(): string
    {
        global $mdParser;
        return $mdParser->text($this->remarksInternal);
    }

}