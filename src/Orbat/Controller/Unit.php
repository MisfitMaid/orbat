<?php

namespace Orbat\Controller;

use Nin\Nin;
use Orbat\Controller;
use Orbat\Snowflake;

class Unit extends Controller
{
    public \Orbat\Model\Unit|bool $unit = false;
    public bool $canEdit = false;

    function __construct($idUnit)
    {
        parent::__construct();
        $this->unit = \Orbat\Model\Unit::findByPk(Snowflake::parse($idUnit));
    }

    public function beforeAction($action)
    {
        $this->addBreadcrumb("Units", "/units");
        if (!$this->unit) {
            $this->displayError('Unit not found.', 404);
            return false;
        }

        if (Nin::user()) {
            foreach ($this->unit->editors as $editor) {
                if ($editor->idUser == Nin::uid()) {
                    $this->canEdit = true;
                }
            }
            if (Nin::user()->isAdmin) {
                $this->canEdit = true;
            }
        }

        $this->addBreadcrumb($this->unit->name, "/unit/" . Snowflake::format($this->unit->idUnit));
        $this->twig->addGlobal("unit", $this->unit);
        $this->twig->addGlobal("canEdit", $this->canEdit);
        return $action;
    }

    public function actionOverview()
    {
        $this->render("unit.overview");
    }
}