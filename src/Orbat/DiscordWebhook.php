<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Orbat\Model\User;

class DiscordWebhook
{
    public string $msg;
    public string $authorName;
    public string $authorImage;
    protected Client $guzzle;

    public function __construct(public string $webhookURL)
    {
        $this->guzzle = new Client([
            'headers' => [
                'User-Agent' => 'misfitmaid/orbat <misfit@misfitmaid.com>',
            ]
        ]);
    }

    /**
     * @todo make this do actual stuff
     */
    public function send(): bool
    {
        $x = [];
        $x['content'] = $this->msg;

        try {
            $this->guzzle->post($this->webhookURL, ['json' => $x]);
            return true;
        } catch (GuzzleException $e) {
            return false;
        }


    }

    public function setAuthor(User $user): void
    {
        $this->authorName = $user->displayName;
        $this->authorImage = $user->getAvatarURL();
    }
}