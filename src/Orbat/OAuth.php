<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat;

use Nin\Nin;
use Wohali\OAuth2\Client\Provider\Discord;

class OAuth
{
    public Discord $provider;

    public function __construct()
    {
        $this->provider = new Discord([
            'clientId' => nf_param('discord.id'),
            'clientSecret' => nf_param('discord.secret'),
            'redirectUri' => PHP_SAPI == 'cli-server' ? 'http://localhost:8000/auth' : 'https://orbat.misfitmaid.com/auth'
        ]);
    }

    public function getRedirect(): string
    {
        $url = $this->provider->getAuthorizationUrl(['scope' => ['identify']]) . "&prompt=none";
        Nin::setSession('oauth2state', $this->provider->getState());
        return $url;
    }

    public function handleLogin()
    {

    }
}