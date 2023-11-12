<?php
/*
 * Copyright (c) 2023 Keira Dueck <sylae@calref.net>
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Orbat;

use Nin\Nin;
use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\TwigFunction;

class Controller extends \Nin\Controller
{
    public Environment $twig;

    public array $breadcrumb = [];

    public function __construct()
    {
        // $this->setupSentry();
        $loader = new \Twig\Loader\FilesystemLoader('tpl');
        $this->twig = new \Twig\Environment($loader, [
            'cache' => false,
            'debug' => php_sapi_name() == 'cli-server',
            'strict_variables' => php_sapi_name() == 'cli-server',
        ]);
        $this->twig->addExtension(new IntlExtension());
        $this->twig->addFunction(new TwigFunction('elapsedTime', function () {
            return microtime(true) - TIME_INIT;
        }));
        if (php_sapi_name() == 'cli-server') {
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        }

        $this->twig->addFilter(new \Twig\TwigFilter('snowflake', function ($snow) {
            return Snowflake::format($snow);
        }));

        $this->twig->addGlobal("ui", nf_param("ui"));
        $this->twig->addGlobal("uic", [
            'svgLogo' => file_get_contents("img/valkyrie.svg")
        ]);
    }

    /**
     * do this in here to prevent nin's error handler from slurping away our exceptions?
     */
    public function setupSentry()
    {

        // dsn config is safe to commit i guess? https://docs.sentry.io/product/sentry-basics/dsn-explainer/#dsn-utilization
        \Sentry\init([
            'dsn' => 'https://940ff3052b67415b9fb42e0195198f26@o4503919061565440.ingest.sentry.io/4505489899978752',
            'traces_sample_rate' => 1.0,
            'profiles_sample_rate' => 1.0,
            'environment' => php_sapi_name() == 'cli-server' ? 'dev' : 'prod',
            'max_request_body_size' => 'always',
            'enable_tracing' => true,
        ]);

        \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
            if (Nin::user()) {
                $scope->setUser([
                    'id' => Nin::user()->idUser,
                    'username' => Nin::user()->username
                ]);
            }
        });
    }

    public function renderPartial($view, $options = [])
    {
        return $this->twig->render($view . ".twig", $options);
    }

    public function render($view, $options = [])
    {
        $this->twig->addGlobal("user", Nin::user());
        $this->twig->addGlobal("csrf", Nin::getSession("csrf_token"));
        if (count($this->breadcrumb) > 0) {
            $this->twig->addGlobal("breadcrumb", array_merge([["text" => "Home", "a" => "/"]], $this->breadcrumb));
        }
        echo $this->twig->render($view . ".twig", $options);
    }

    public function addBreadcrumb(string $title, string $url): void
    {
        $this->breadcrumb[] = ["text" => $title, "a" => $url];
    }


}