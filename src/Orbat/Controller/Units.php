<?php

namespace Orbat\Controller;

use Intervention\Image\ImageManager;
use Nin\Nin;
use Orbat\Controller;
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
                $unit->icon = base64_encode($img->encode("png"));
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