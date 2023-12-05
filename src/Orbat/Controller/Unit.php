<?php

namespace Orbat\Controller;

use Carbon\Carbon;
use Nin\Nin;
use Orbat\Controller;
use Orbat\FileUtilities;
use Orbat\Model\Endorsement;
use Orbat\Model\Group;
use Orbat\Model\Medal;
use Orbat\Model\Member;
use Orbat\Model\MemberEndorsement;
use Orbat\Model\Rank;
use Orbat\Model\UnitEditor;
use Orbat\Snowflake;

class Unit extends Controller
{
    public \Orbat\Model\Unit|bool $unit = false;
    public bool $canEdit = false;

    function __construct($idUnit)
    {
        parent::__construct();
        $this->unit = \Orbat\Model\Unit::findByPk(Snowflake::parse($idUnit));

        if (!$this->unit) {
            $this->unit = \Orbat\Model\Unit::findByAttributes(['slug' => $idUnit]);
        }
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

        $this->addBreadcrumb($this->unit->name, "/unit/" . $this->unit->slug());
        $this->twig->addGlobal("unit", $this->unit);
        $this->twig->addGlobal("canEdit", $this->canEdit);
        return $action;
    }

    public function actionOverview()
    {
        $this->twig->addGlobal("activeMenu", "overview");
        $this->render("unit.overview");
    }

    public function actionOperations()
    {
        $this->twig->addGlobal("activeMenu", "ops");
        $this->addBreadcrumb("Operations", sprintf("/unit/%s/operations",
            $this->unit->slug()));
        $this->render("unit.operations");
    }

    public function actionRoster()
    {
        $this->twig->addGlobal("activeMenu", "roster");
        $this->addBreadcrumb("Roster", sprintf("/unit/%s/roster",
            $this->unit->slug()));

        if ($this->canEdit) {
            if (isset($_POST['csrf'])) {
                if ($_POST['csrf'] !== \Nin\Nin::getSession('csrf_token')) {
                    $this->displayError('Invalid token.');
                    return false;
                }
            }

            if (isset($_POST['delete'])) {
                /** @var Member $mem */
                $mem = Member::findByPk($_POST['idMember'] ?? 0);
                if (!$mem || $mem->idUnit != $this->unit->idUnit) {
                    $this->displayError('Invalid member.');
                    return false;
                }

                $mem->remove();
            }
        }

        $this->render("unit.roster");
    }

    public function actionMemberAdd($idMember = null)
    {
        $this->twig->addGlobal("activeMenu", "roster");
        $this->addBreadcrumb("Roster", sprintf("/unit/%s/roster",
            $this->unit->slug()));

        if ($idMember != "add") {
            /** @var Member $editMember */
            $editMember = Member::findByPk(Snowflake::parse($idMember));
            if (!$editMember || $editMember->idUnit != $this->unit->idUnit) {
                $this->displayError("Invalid member");
                return false;
            }
        }
        if ($this->canEdit) {
            if (isset($_POST['submit']) && isset($_POST['csrf'])) {
                if ($_POST['csrf'] !== \Nin\Nin::getSession('csrf_token')) {
                    $this->displayError('Invalid token.');
                    return false;
                }

                /** @var Rank $rank */
                $rank = Rank::findByPk($_POST['rank']);
                if (!$rank || $rank->idUnit != $this->unit->idUnit) {
                    $this->displayError("Invalid rank");
                    return false;
                }

                /** @var Group $group */
                if ($_POST['group'] != 0) {
                    $group = Group::findByPk($_POST['group']);
                    if (!$group || $group->idUnit != $this->unit->idUnit) {
                        $this->displayError("Invalid group");
                        return false;
                    }
                } else {
                    $group = null;
                }

                /** @var Endorsement[] $endorsements */
                $endorsements = [];
                foreach ($_POST['endorsements'] ?? [] as $v) {
                    $end = Endorsement::findByPk($v);
                    if (!$end || $end->idUnit != $this->unit->idUnit) {
                        $this->displayError("Invalid endorsement");
                        return false;
                    }
                    $endorsements[] = $end;
                }

                if ($idMember == "add") {
                    $mem = new Member();
                    $mem->idUnit = $this->unit->idUnit;
                    $mem->idRank = $rank->idRank;
                    if (!is_null($group)) {
                        $mem->idGroup = $group->idGroup;
                    }
                    $mem->name = trim($_POST['name']);
                    $mem->role = trim($_POST['role'] ?? "");
                    $mem->playerName = trim($_POST['player'] ?? "");
                    $mem->dateJoined = new Carbon($_POST['dateJoined']);
                    $mem->dateLastPromotion = $mem->dateJoined;
                    $mem->remarks = trim($_POST['remarks']);
                    $mem->remarksInternal = trim($_POST['remarksInternal']);
                    $snow = Snowflake::generate();
                    $mem->idMember = $snow;
                    $mem->save();

                    // add our endorsements
                    foreach ($endorsements as $end) {
                        $me = new MemberEndorsement();
                        $me->idMember = $mem->idMember;
                        $me->idEndorsement = $end->idEndorsement;
                        $me->save();
                    }

                    $this->redirect(sprintf("/unit/%s/roster/%s",
                        $this->unit->slug(),
                        Snowflake::format($snow)));
                } else {
                    $editMember->name = trim($_POST['name']);
                    $editMember->role = trim($_POST['role'] ?? "");
                    $editMember->playerName = trim($_POST['player'] ?? "");
                    $editMember->dateJoined = new Carbon($_POST['dateJoined']);
                    $editMember->remarks = trim($_POST['remarks']);
                    $editMember->remarksInternal = trim($_POST['remarksInternal']);
                    if ($editMember->idRank != $rank->idRank) {
                        $editMember->idRank = $rank->idRank;
                        $editMember->dateLastPromotion = Carbon::now();
                    }
                    if (!is_null($group)) {
                        $editMember->idGroup = $group->idGroup;
                    } else {
                        $editMember->idGroup = null;
                    }

                    foreach ($this->unit->endorsements as $endorsement) {
                        $desired = in_array($endorsement, $endorsements);
                        $has = false;
                        foreach ($editMember->endorsements as $me) {
                            if ($me->idEndorsement == $endorsement->idEndorsement) {
                                $has = true;
                            }
                        }

                        if ($desired && !$has) {
                            $me = new MemberEndorsement();
                            $me->idMember = $editMember->idMember;
                            $me->idEndorsement = $endorsement->idEndorsement;
                            $me->save();
                        }

                        if (!$desired) {
                            foreach ($editMember->endorsements as $end) {
                                if ($end->idEndorsement == $endorsement->idEndorsement) {
                                    $end->remove();
                                }
                            }
                        }
                    }
                    $editMember->save();

                    $this->redirect(sprintf("/unit/%s/roster/%s",
                        $this->unit->slug(),
                        Snowflake::format($editMember->idMember)));
                }
                return true;
            }

            if ($idMember != "add") {
                // editing a member
                $this->addBreadcrumb($editMember->name, sprintf("/unit/%s/roster/%s",
                    $this->unit->slug(),
                    Snowflake::format($editMember->idMember)));

                $end = [];
                foreach ($editMember->endorsements as $me) {
                    if (!in_array($me->idEndorsement, $end)) {
                        $end[] = $me->idEndorsement;
                    }
                }
                $config = ['member' => $editMember, 'form' => ['submit' => 'Edit Member', 'endorsements' => $end]];
                $this->render("member.edit", $config);
            } else {
                // adding a member
                $this->addBreadcrumb("Add member", sprintf("/unit/%s/roster/new",
                    $this->unit->slug()));

                $empty = new Member();
                $empty->idUnit = $this->unit->idUnit;
                $empty->idRank = 0;
                $empty->name = "";
                $empty->role = "";
                $empty->playerName = "";
                $empty->dateJoined = Carbon::now();
                $empty->dateLastPromotion = Carbon::now();
                $empty->remarks = "";
                $empty->remarksInternal = "";

                $config = ['member' => $empty, 'form' => ['submit' => 'Add Member', 'endorsements' => []]];
                $this->render("member.add", $config);
            }

        } else {
            if (!Nin::user()) {
                $this->redirect("/login");
                return false;
            }
            $this->displayError("No permissions to edit unit", 403);
        }
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

    public function actionConfig()
    {
        $this->twig->addGlobal("activeMenu", "config");
        $this->addBreadcrumb("Configuration", sprintf("/unit/%s/config",
            $this->unit->slug()));
        if ($this->canEdit) {
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

                if ($s == "") {
                    $this->unit->slug = null;
                } else {
                    if (\Orbat\Model\Unit::countByAttributes(["slug" => $s]) > 0) {
                        $this->displayError("slug already in use");
                        return false;
                    }
                    $this->unit->slug = $s;
                }

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
        $this->twig->addGlobal("activeMenu", "config");
        $this->addBreadcrumb("Configuration", sprintf("/unit/%s/config",
            $this->unit->slug()));
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
        } else {
            if (!Nin::user()) {
                $this->redirect("/login");
                return false;
            }
            $this->displayError("No permissions to edit unit", 403);
        }
    }

    public function actionConfigMedals()
    {
        $this->twig->addGlobal("activeMenu", "config");
        $this->addBreadcrumb("Configuration", sprintf("/unit/%s/config",
            $this->unit->slug()));
        if ($this->canEdit) {
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
        } else {
            if (!Nin::user()) {
                $this->redirect("/login");
                return false;
            }
            $this->displayError("No permissions to edit unit", 403);
        }
    }

    public function actionConfigEditors()
    {
        $this->twig->addGlobal("activeMenu", "config");
        $this->addBreadcrumb("Configuration", sprintf("/unit/%s/config",
            $this->unit->slug()));
        if ($this->canEdit) {
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
        } else {
            if (!Nin::user()) {
                $this->redirect("/login");
                return false;
            }
            $this->displayError("No permissions to edit unit", 403);
        }
    }

    public function actionConfigGroups()
    {
        $this->twig->addGlobal("activeMenu", "config");
        $this->addBreadcrumb("Configuration", sprintf("/unit/%s/config",
            $this->unit->slug()));
        if ($this->canEdit) {
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
        } else {
            if (!Nin::user()) {
                $this->redirect("/login");
                return false;
            }
            $this->displayError("No permissions to edit unit", 403);
        }
    }

    public function actionConfigEndorsements()
    {
        $this->twig->addGlobal("activeMenu", "config");
        $this->addBreadcrumb("Configuration", sprintf("/unit/%s/config",
            $this->unit->slug()));
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
        } else {
            if (!Nin::user()) {
                $this->redirect("/login");
                return false;
            }
            $this->displayError("No permissions to edit unit", 403);
        }
    }
}