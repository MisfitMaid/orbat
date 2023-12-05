<?php

namespace Orbat\Controller;

class UnitOps extends UnitBase
{

    public function actionOperations()
    {
        $this->twig->addGlobal("activeMenu", "ops");
        $this->addBreadcrumb("Operations", sprintf("/unit/%s/operations",
            $this->unit->slug()));
        $this->render("unit.operations");
    }

}
