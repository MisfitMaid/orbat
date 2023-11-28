<?php

namespace Orbat\Controller;

use Nin\Nin;
use Orbat\Controller;
use Orbat\FileUtilities;
use Orbat\Model\Unit;
use Orbat\Model\UnitEditor;
use Orbat\Snowflake;

class Units extends Controller
{
    public function beforeAction($action)
    {
        $this->addBreadcrumb("Units", "/units");
        return $action;
    }

    public function actionCreate()
    {
        if (!Nin::user()) {
            $this->redirect("/login");
            return false;
        }

        $this->addBreadcrumb("Create unit", "/units/create");

        if (!Nin::user()->isAdmin) {
            $this->displayError("If you are interested in creating a unit, please get in touch. Right now things are under construction so yeah.", 403);
            return false;
        }

        if (isset($_POST['csrf'])) {
            if ($_POST['csrf'] !== \Nin\Nin::getSession('csrf_token')) {
                $this->displayError('Invalid token.');
                return false;
            }
            $name = trim($_POST['name']);

            if (mb_strlen($name) == 0 || mb_strlen($name) > 64) {
                $this->displayError("Name is required, please try again", 400);
                return false;
            }

            $unit = new Unit();
            $unit->name = $name;
            $unit->idUnit = Snowflake::generate();

            if (array_key_exists('icon', $_FILES)) {
                try {
                    $unit->icon = FileUtilities::sanitizeUpload($_FILES['icon'], 256, 256);
                } catch (\Exception $e) {
                    $this->displayError($e->getMessage());
                    return false;
                }
            }

            $editor = new UnitEditor();
            $editor->idUser = Nin::uid();
            $editor->idUnit = $unit->idUnit;
            $unit->save();
            $editor->save();

            $this->redirect("/unit/" . Snowflake::format($unit->idUnit));
        } else {
            $this->render("units.create", []);
        }
    }
}