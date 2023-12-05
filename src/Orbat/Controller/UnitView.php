<?php

namespace Orbat\Controller;

use Orbat\Model\Medal;
use Orbat\Model\Rank;
use Orbat\Snowflake;

class UnitView extends UnitBase
{
    public function actionOverview()
    {
        $this->twig->addGlobal("activeMenu", "overview");
        $this->render("unit.overview");
    }

    public function actionIcon()
    {
        if (is_null($this->unit->icon)) {
            $this->displayError('Unit has no icon.', 404);
            return false;
        }

        $av = base64_decode($this->unit->icon);

        header("Content-Type: image/png");
        header('Content-Length: ' . strlen($av));
        header("Digest: sha256-" . base64_encode(hash("sha256", $av, true)));
        echo $av;
    }

    public function actionRankIcon($idRank)
    {
        /** @var Rank $rank */
        $rank = Rank::findByPk(Snowflake::parse($idRank));
        if (!$rank || $rank->idUnit != $this->unit->idUnit) {
            $this->displayError("Invalid Rank ID");
            return false;
        }

        if (!$rank->icon) {
            $this->displayError("No rank icon", 404);
        }

        $av = base64_decode($rank->icon);

        header("Content-Type: image/png");
        header('Content-Length: ' . strlen($av));
        header("Digest: sha256-" . base64_encode(hash("sha256", $av, true)));
        echo $av;
    }

    public function actionMedalIcon($idMedal)
    {
        /** @var Medal $medal */
        $medal = Medal::findByPk(Snowflake::parse($idMedal));
        if (!$medal || $medal->idUnit != $this->unit->idUnit) {
            $this->displayError("Invalid Medal ID");
            return false;
        }

        $av = base64_decode($medal->image);

        header("Content-Type: image/png");
        header('Content-Length: ' . strlen($av));
        header("Digest: sha256-" . base64_encode(hash("sha256", $av, true)));
        echo $av;
    }
}