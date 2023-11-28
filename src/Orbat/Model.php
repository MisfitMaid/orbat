<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat;

use Carbon\Carbon;

class Model extends \Nin\Model implements \JsonSerializable
{

    public function __isset($name)
    {
        // Test relations
        $relations = $this->relations();
        if (array_key_exists($name, $relations)) {
            return true;
        }

        // Test getters
        $functionname = 'get' . ucfirst($name);
        if (method_exists($this, $functionname)) {
            return true;
        }

        // Test columns
        if (isset($this->_data[$name])) {
            return true;
        }

        // Nothing found
        return false;
    }

    public function jsonSerialize(): mixed
    {
        $data = [];
        foreach ($this->_data as $k => $v) {
            if (in_array($k, ["icon", "image"])) {
                $data[$k] = !is_null($v);
            } else {
                $data[$k] = $this->__get($k);
            }
        }
        return $data;
    }

    public function __get($name)
    {
        if (in_array($name, ['dateCreated', 'dateUpdated', 'dateJoined', 'dateLastPromotion'])) {
            return new Carbon(parent::__get($name));
        } elseif (str_starts_with($name, "is")) {
            return (bool)parent::__get($name);
        } else {
            return parent::__get($name);
        }
    }
}