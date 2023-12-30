<?php

namespace Orbat\Controller;

use Orbat\FileUtilities;
use Orbat\Model\Endorsement;
use Orbat\Model\Group;
use Orbat\Model\Medal;
use Orbat\Model\Rank;
use Orbat\Model\UnitEditor;
use Orbat\Snowflake;

class UnitConfig extends UnitBase
{
    public function beforeAction($action)
    {
        $action = parent::beforeAction($action);
        if (!$action) {
            return false;
        }
        if ($this->requireEdit()) {
            $this->twig->addGlobal("activeMenu", "config");
            $this->addBreadcrumb("Configuration", sprintf("/unit/%s/config",
                $this->unit->slug()));
            return $action;
        } else {
            return false;
        }
    }

    public function actionConfig()
    {
        if (isset($_POST['csrf'])) {
            if ($_POST['csrf'] !== \Nin\Nin::getSession('csrf_token')) {
                $this->displayError('Invalid token.');
                return false;
            }
            $this->unit->name = trim($_POST['name']);

            $s = \Normalizer::normalize(mb_strtolower(trim($_POST['slug'] ?? "")));

            if (str_contains($s, "/") || str_contains($s, "\\") || mb_strlen($s) > 32) {
                $this->displayError("Invalid slug (max 32 unicode chars, no / or \\ allowed");
                return false;
            }

            if ($s != $this->unit->slug) {
                if ($s == "") {
                    $this->unit->slug = null;
                } else {
                    if (\Orbat\Model\Unit::countByAttributes(["slug" => $s]) > 0) {
                        $this->displayError("slug already in use");
                        return false;
                    }
                    $this->unit->slug = $s;
                }
            }

            $this->unit->discordInvite = trim($_POST['discordInvite'] ?? null);

            if (mb_strlen($this->unit->name) == 0 || mb_strlen($this->unit->name) > 64) {
                $this->displayError("Name is required, please try again", 400);
                return false;
            }

            if (array_key_exists('icon', $_FILES) && $_FILES['icon']['error'] != UPLOAD_ERR_NO_FILE) {
                try {
                    $this->unit->icon = FileUtilities::sanitizeUpload($_FILES['icon'], 256, 256);
                } catch (\Exception $e) {
                    $this->displayError($e->getMessage());
                    return false;
                }
            }
            $this->unit->save();
        }

        if (!isset($this->unit->slug)) {
            $this->unit->slug = "";
        }
        $this->render("unit.config");
    }

    public function actionConfigRanks()
    {
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

                if (array_key_exists('icon', $_FILES)) {
                    try {
                        $r->icon = FileUtilities::sanitizeUpload($_FILES['icon'], 64, 64);
                    } catch (\Exception $e) {
                        $this->displayError($e->getMessage());
                        return false;
                    }
                }

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

                if (array_key_exists('icon', $_FILES)) {
                    try {
                        $rank->icon = FileUtilities::sanitizeUpload($_FILES['icon'], 64, 64);
                    } catch (\Exception $e) {
                        $this->displayError($e->getMessage());
                        return false;
                    }
                }

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
    }

    public function actionConfigMedals()
    {
        if (isset($_POST['csrf'])) {
            if ($_POST['csrf'] !== \Nin\Nin::getSession('csrf_token')) {
                $this->displayError('Invalid token.');
                return false;
            }

            $weight = (int)$_POST['weight'];
            $name = trim($_POST['name']);

            if (mb_strlen($name) == 0) {
                $this->displayError("name must not be empty");
                return false;
            }

            if (array_key_exists("medal_new", $_POST)) {
                $m = new Medal();
                $m->idUnit = $this->unit->idUnit;
                $m->idMedal = Snowflake::generate();
                $m->weight = $weight;
                $m->name = $name;

                if (array_key_exists('image', $_FILES)) {
                    try {
                        $m->image = FileUtilities::sanitizeUpload($_FILES['image'], 240, 64);
                    } catch (\Exception $e) {
                        $this->displayError($e->getMessage());
                        return false;
                    }
                }

                $m->save();
            }

            if (array_key_exists("medal_edit", $_POST)) {
                /** @var Medal $medal */
                $medal = Medal::findByPk($_POST['idMedal']);
                if (!$medal || $medal->idUnit != $this->unit->idUnit) {
                    $this->displayError("Invalid Medal ID");
                    return false;
                }

                $medal->weight = $weight;
                $medal->name = $name;

                if (array_key_exists('image', $_FILES)) {
                    try {
                        $medal->image = FileUtilities::sanitizeUpload($_FILES['image'], 240, 64);
                    } catch (\Exception $e) {
                        $this->displayError($e->getMessage());
                        return false;
                    }
                }

                $medal->save();
            }

            if (array_key_exists("rank_delete", $_POST)) {
                /** @var Medal $medal */
                $medal = Medal::findByPk($_POST['idMedal']);
                if (!$medal || $medal->idUnit != $this->unit->idUnit) {
                    $this->displayError("Invalid Medal ID");
                    return false;
                }
                $medal->remove();
            }


        }
        $this->render("unit.config.medals");
    }

    public function actionConfigEditors()
    {
        if (isset($_POST['csrf'])) {
            if ($_POST['csrf'] !== \Nin\Nin::getSession('csrf_token')) {
                $this->displayError('Invalid token.');
                return false;
            }

            //stuff
            if (isset($_POST['editor_add'])) {
                /** @var \Orbat\Model\User $user */
                $user = \Orbat\Model\User::findByAttributes(['username' => trim($_POST['username'] ?? "")]);

                if (!$user) {
                    $this->displayError("unable to find user! make sure they've logged in at least once before.");
                    return false;
                }

                foreach ($this->unit->editors as $ue) {
                    if ($ue->idUser == $user->idUser) {
                        $this->displayError("User is already an editor! aborting");
                        return false;
                    }
                }

                $ue = new UnitEditor();
                $ue->idUnit = $this->unit->idUnit;
                $ue->idUser = $user->idUser;
                $ue->save();

            } elseif (isset($_POST['editor_remove'])) {
                /** @var UnitEditor $ue */
                $ue = UnitEditor::findByPk($_POST['idEditor']);

                if (!$ue || $ue->idUnit != $this->unit->idUnit) {
                    $this->displayError("invalid editor ID");
                    return false;
                }

                if (count($this->unit->editors) <= 1) {
                    $this->displayError("Cannot remove the last editor! If you would like to nuke the unit, please get in touch and we can do it manually.");
                    return false;
                }

                $ue->remove();
            }
        }
        $this->render("unit.config.editors");
    }

    public function actionConfigGroups()
    {
        if (isset($_POST['csrf'])) {
            if ($_POST['csrf'] !== \Nin\Nin::getSession('csrf_token')) {
                $this->displayError('Invalid token.');
                return false;
            }

            $weight = (int)$_POST['weight'];
            $parent = $_POST['parent'] ?? null;
            if ($parent == 0) {
                $parent = null;
            }
            $name = trim($_POST['name']);
            $color = $_POST['color'] ?? null;

            if (mb_strlen($name) == 0) {
                $this->displayError("name must not be empty");
                return false;
            }

            if (array_key_exists("group_new", $_POST)) {
                $g = new Group();
                $g->idGroup = Snowflake::generate();
                $g->idUnit = $this->unit->idUnit;
                $g->idParent = $parent;
                $g->weight = $weight;
                $g->name = $name;
                $g->color = $color;
                $g->save();
            }

            if (array_key_exists("group_edit", $_POST)) {
                /** @var Group $group */
                $group = Group::findByPk($_POST['idGroup']);
                if (!$group || $group->idUnit != $this->unit->idUnit) {
                    $this->displayError("Invalid Group ID");
                    return false;
                }

                $group->idParent = $parent;
                $group->weight = $weight;
                $group->name = $name;
                $group->color = $color;
                $group->save();
            }

            if (array_key_exists("group_delete", $_POST)) {
                /** @var Group $group */
                $group = Group::findByPk($_POST['idGroup']);
                if (!$group || $group->idUnit != $this->unit->idUnit) {
                    $this->displayError("Invalid Group ID");
                    return false;
                }
                $group->remove();
            }


        }
        $this->render("unit.config.groups");
    }

    public function actionConfigEndorsements()
    {
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

            if (array_key_exists("endorsement_new", $_POST)) {
                $r = new Endorsement();
                $r->idUnit = $this->unit->idUnit;
                $r->idEndorsement = Snowflake::generate();
                $r->weight = $weight;
                $r->abbr = $abbr;
                $r->name = $name;
                $r->save();
            }

            if (array_key_exists("endorsement_edit", $_POST)) {
                /** @var Endorsement $end */
                $end = Endorsement::findByPk($_POST['idEndorsement']);
                if (!$end || $end->idUnit != $this->unit->idUnit) {
                    $this->displayError("Invalid Endorsement ID");
                    return false;
                }

                $end->weight = $weight;
                $end->abbr = $abbr;
                $end->name = $name;
                $end->save();
            }

            if (array_key_exists("endorsement_delete", $_POST)) {
                /** @var Endorsement $end */
                $end = Endorsement::findByPk($_POST['idEndorsement']);
                if (!$end || $end->idUnit != $this->unit->idUnit) {
                    $this->displayError("Invalid Endorsement ID");
                    return false;
                }
                $end->remove();
            }


        }
        $this->render("unit.config.endorsements");
    }
}