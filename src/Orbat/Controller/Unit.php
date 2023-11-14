<?php

namespace Orbat\Controller;

use Intervention\Image\ImageManager;
use Nin\Nin;
use Orbat\Controller;
use Orbat\Model\Rank;
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

    public function actionConfig()
    {
        if ($this->canEdit) {
            if (isset($_POST['csrf'])) {
                if ($_POST['csrf'] !== \Nin\Nin::getSession('csrf_token')) {
                    $this->displayError('Invalid token.');
                    return false;
                }
                $this->unit->name = trim($_POST['name']);

                if (mb_strlen($this->unit->name) == 0 || mb_strlen($this->unit->name) > 64) {
                    $this->displayError("Name is required, please try again", 400);
                    return false;
                }

                if (array_key_exists('icon', $_FILES)) {
                    $file = $_FILES['icon'];

                    if (!in_array($file['type'], ['image/png', 'image/jpeg'])) {
                        $this->displayError('invalid image format');
                        return false;
                    }

                    $manager = new ImageManager();
                    $img = $manager->make($file['tmp_name']);
                    $img->fit(256, 256, function ($constraint) {
                        $constraint->upsize();
                    });
                    $this->unit->icon = base64_encode($img->encode("png"));
                }
                $this->unit->save();
            }
            $this->render("unit.config");
        } else {
            if (!Nin::user()) {
                $this->redirect("/login");
                return false;
            }
            $this->displayError("No permissions to edit unit", 403);
        }
    }

    public function actionConfigRanks()
    {
        if ($this->canEdit) {
            if (isset($_POST['csrf'])) {
                if ($_POST['csrf'] !== \Nin\Nin::getSession('csrf_token')) {
                    $this->displayError('Invalid token.');
                    return false;
                }

                $weight = (int)$_POST['weight'];
                $abbr = trim($_POST['abbr']);
                $name = trim($_POST['name']);

                if (mb_strlen($abbr) == 0 || mb_strlen($name) == 0) {
                    $this->displayError("abbr or name must not be empty");
                    return false;
                }

                if (array_key_exists("rank_new", $_POST)) {
                    $r = new Rank();
                    $r->idUnit = $this->unit->idUnit;
                    $r->idRank = Snowflake::generate();
                    $r->weight = $weight;
                    $r->abbr = $abbr;
                    $r->name = $name;
                    $r->save();
                }

                if (array_key_exists("rank_edit", $_POST)) {
                    /** @var Rank $rank */
                    $rank = Rank::findByPk($_POST['idRank']);
                    if (!$rank || $rank->idUnit != $this->unit->idUnit) {
                        $this->displayError("Invalid Rank ID");
                        return false;
                    }

                    $rank->weight = $weight;
                    $rank->abbr = $abbr;
                    $rank->name = $name;
                    $rank->save();
                }

                if (array_key_exists("rank_delete", $_POST)) {
                    /** @var Rank $rank */
                    $rank = Rank::findByPk($_POST['idRank']);
                    if (!$rank || $rank->idUnit != $this->unit->idUnit) {
                        $this->displayError("Invalid Rank ID");
                        return false;
                    }
                    $rank->remove();
                }


            }
            $this->render("unit.config.ranks");
        } else {
            if (!Nin::user()) {
                $this->redirect("/login");
                return false;
            }
            $this->displayError("No permissions to edit unit", 403);
        }
    }
}