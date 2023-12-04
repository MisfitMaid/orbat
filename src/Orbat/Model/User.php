<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Model;

use Carbon\Carbon;

/**
 * @property int $idUser
 * @property string $username
 * @property ?string $displayName
 * @property ?string $avatar
 * @property ?string $banner
 * @property Carbon $dateCreated
 * @property Carbon $dateUpdated
 * @property bool $isAdmin
 * @property bool $isMod
 * @property bool $isBanned
 *
 * @property DbSession[] $sessions
 * @property UnitEditor[] $editableUnits
 */
class User extends \Orbat\Model
{
    public static function tablename()
    {
        return 'users';
    }

    public static function primarykey()
    {
        return 'idUser';
    }

    public function relations()
    {
        return [
            'sessions' => [HAS_MANY, "Orbat\Model\DbSession", 'idUser'],
            'editableUnits' => [HAS_MANY, "Orbat\Model\UnitEditor", 'idUser'],
        ];
    }

    public function getBannerURL(): string
    {
        $extension = str_starts_with($this->banner, "a_") ? "gif" : "png";
        return sprintf("https://cdn.discordapp.com/banners/%s/%s.%s", $this->idUser, $this->banner, $extension);
    }

    public function getAvatarURL(): string
    {
        if (is_null($this->avatar)) {
            return $this->getDefaultAvatarURL();
        }
        $extension = str_starts_with($this->avatar, "a_") ? "gif" : "png";
        return sprintf("https://cdn.discordapp.com/avatars/%s/%s.%s", $this->idUser, $this->avatar, $extension);
    }

    private function getDefaultAvatarURL(): string
    {
        return sprintf("https://cdn.discordapp.com/embed/avatars/%s.png", ($this->idUser >> 22) % 6);

    }

    public function remove()
    {
        foreach ($this->sessions as $s) {
            $s->remove();
        }
        foreach ($this->editableUnits as $ue) {
            $ue->remove();
        }
        return parent::remove();
    }
}