<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat\Controller;

use Orbat\Controller;

class API extends Controller
{
    public function beforeAction($action)
    {
        header('Content-Type: application/json');
        return $action;
    }

    public function actionDeployWebsite()
    {
        $hmac = nf_param('deployHMAC');
        if (!$hmac) {
            $this->displayError('remote deploy disabled', 503);
            return;
        }

        $testCode = hash_hmac("sha256", file_get_contents('php://input'), $hmac);
        $givenCode = apache_request_headers()['X-Hub-Signature'] ?? "";

        if (hash_equals($testCode, $givenCode)) {
            $resp = `./update`;
            echo json_encode(['command' => $resp]);
        } else {
            $this->displayError('invalid hmac', 403);
        }
    }

    public function displayError($error, $code = 500)
    {
        http_response_code($code);
        echo json_encode(['_error' => $error]);
    }

}