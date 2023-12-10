<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

use Orbat\DatabaseSessionHandler;

require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/nin/nf.php';

date_default_timezone_set('UTC');
define("TIME_INIT", microtime(true));

if (file_exists(__DIR__ . "/GIT_DESCRIBE")) {
    define("GIT_DESCRIBE", trim(file_get_contents(__DIR__ . "/GIT_DESCRIBE")));
} else {
    define("GIT_DESCRIBE", trim(`git describe --tags --dirty --always`));
}
if (file_exists(__DIR__ . "/GIT_HASH")) {
    define("GIT_HASH", trim(file_get_contents(__DIR__ . "/GIT_HASH")));
} else {
    define("GIT_HASH", trim(`git rev-parse head`));
}

nf_route("/", "Orbat\Controller\Index.Index");
nf_route("/privacy", "Orbat\Controller\Index.Privacy");

nf_route("/login", "Orbat\Controller\User.Login");
nf_route("/logout", "Orbat\Controller\User.Logout");
nf_route("/auth", "Orbat\Controller\User.Auth");

nf_route("/units/create", "Orbat\Controller\Units.Create");

nf_route("/unit/:idUnit", "Orbat\Controller\UnitView.Overview");
nf_route("/unit/:idUnit/icon", "Orbat\Controller\UnitView.Icon");
nf_route("/unit/:idUnit/ranks/:idRank/icon", "Orbat\Controller\UnitView.RankIcon");
nf_route("/unit/:idUnit/medals/:idMedal/icon", "Orbat\Controller\UnitView.MedalIcon");

nf_route("/unit/:idUnit/roster", "Orbat\Controller\UnitRoster.Roster");
nf_route("/unit/:idUnit/roster/add", "Orbat\Controller\UnitRoster.MemberAdd");
nf_route("/unit/:idUnit/roster/:idMember", "Orbat\Controller\UnitRoster.MemberView");
nf_route("/unit/:idUnit/roster/:idMember/edit", "Orbat\Controller\UnitRoster.MemberAdd");

nf_route("/unit/:idUnit/operations", "Orbat\Controller\UnitOps.Operations");

nf_route("/unit/:idUnit/config", "Orbat\Controller\UnitConfig.Config");
nf_route("/unit/:idUnit/config/ranks", "Orbat\Controller\UnitConfig.ConfigRanks");
nf_route("/unit/:idUnit/config/medals", "Orbat\Controller\UnitConfig.ConfigMedals");
nf_route("/unit/:idUnit/config/editors", "Orbat\Controller\UnitConfig.ConfigEditors");
nf_route("/unit/:idUnit/config/groups", "Orbat\Controller\UnitConfig.ConfigGroups");
nf_route("/unit/:idUnit/config/endorsements", "Orbat\Controller\UnitConfig.ConfigEndorsements");

nf_route("/api/deploy", "Orbat\Controller\API.DeployWebsite");

$defaultConfig = [
    'name' => 'ORBAT',
    'debug' => php_sapi_name() == 'cli-server' || file_exists(__DIR__ . "/DEBUG"),
    'routing' => [
        'preferRules' => false,
        'rules' => [
            '/^\\/(?<path>[a-z0-9\\-_\\/]+)$/' => "Orbat\Controller\Error.404",
        ],
    ],
    'cache' => [
        'class' => 'APCu',
        'options' => [
            'prefix' => 'orbat_',
        ],
    ],
    'user' => [
        'model' => 'Orbat\Model\User'
    ],
];

$mdParser = new \ParsedownExtra();
$mdParser->setSafeMode(true);

$cfg = array_merge($defaultConfig, require_once 'config.php');
nf_config_initialize($cfg);
nf_db_initialize();

session_set_save_handler(new DatabaseSessionHandler(), true);
session_start([
    'name' => 'orbat_session',
    'cookie_lifetime' => 86400 * 7,
    'gc_maxlifetime' => 86400 * 7,
    'use_strict_mode' => true,
    'cookie_secure' => php_sapi_name() != 'cli-server',
    'cookie_samesite' => 'Lax',
    'lazy_write' => false,
]);

nf_begin($cfg);