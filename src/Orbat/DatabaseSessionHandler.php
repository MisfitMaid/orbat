<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat;

use Nin\Nin;
use Orbat\Model\DbSession;

class DatabaseSessionHandler implements
    \SessionUpdateTimestampHandlerInterface, \SessionHandlerInterface, \SessionIdInterface
{

    protected array $cache = [];

    public function validateId($id): bool
    {
        return !is_null($this->cacheGet($id));
    }

    protected function cacheGet($id): ?DbSession
    {
        if (!array_key_exists($id, $this->cache)) {
            $x = DbSession::findByPk($id);
            if (!$x) {
                $x = new DbSession();
                $x->idSession = $id;
            }
            $this->cache[$id] = $x;
        }
        return $this->cache[$id] ?? null;
    }

    public function updateTimestamp($id, $data): bool
    {
        return $this->write($id, $data);
    }

    public function write($id, $data): bool
    {
        $m = $this->cacheGet($id);
        $m->data = $data;
        $m->ip = $this->getIP();
        $m->userAgent = $this->getUserAgent();
        $m->idUser = $this->getUserID() ?? 0;
        return $m->save();
    }

    protected function getIP(): ?string
    {
        return inet_pton((PHP_SAPI == "cli") ? "::1" : Nin::ip());
    }

    protected function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    protected function getUserID()
    {
        $uid = Nin::uid();
        if (!$uid) {
            return null;
        }
        return $uid;
    }

    public function close(): bool
    {
        return true;
    }

    public function destroy($id): bool
    {
        $m = $this->cacheGet($id);
        return $m->remove();
    }

    public function gc($max_lifetime): int
    {
        nf_db_context()
            ->query("delete from sessions where dateUpdated < (now() - interval $max_lifetime second)");
        return 0; // nin doesnt give us effected rows
    }

    public function open($path, $name): bool
    {
        return true;
    }

    public function read($id): string
    {
        $m = $this->cacheGet($id);
        return $m->data ?? "";
    }

    public function create_sid(): string
    {
        return base_convert(bin2hex(random_bytes(16)), 16, 36);
    }
}