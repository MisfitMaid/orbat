<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Model;

/**
 * @property int $idSession
 * @property string $data
 * @property string $ip
 * @property string $userAgent
 * @property int $idUser
 *
 * @property User $user
 */
class DbSession extends \Orbat\Model
{
    public static function tablename()
    {
        return 'sessions';
    }

    public static function primarykey()
    {
        return 'idSession';
    }

    public function relations()
    {
        return [
            'user' => [BELONGS_TO, "Orbat\Model\User", 'idUser'],
        ];
    }
}