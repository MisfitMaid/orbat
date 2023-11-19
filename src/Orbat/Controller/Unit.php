<?php

namespace Orbat\Controller;

use Carbon\Carbon;
use Intervention\Image\ImageManager;
use Nin\Nin;
use Orbat\Controller;
use Orbat\Model\Endorsement;
use Orbat\Model\Group;
use Orbat\Model\Member;
use Orbat\Model\MemberEndorsement;
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

    public function actionRoster()
    {
        $this->addBreadcrumb("Roster", sprintf("/unit/%s/roster",
            Snowflake::format($this->unit->idUnit)));
        $this->render("unit.roster");
    }

    public function actionMemberAdd($idMember = null)
    {
        $this->addBreadcrumb("Roster", sprintf("/unit/%s/roster",
            Snowflake::format($this->unit->idUnit)));

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
                    $mem->name = trim($_POST['name']);
                    $mem->role = trim($_POST['role'] ?? "");
                    $mem->dateJoined = new Carbon($_POST['dateJoined']);
                    $mem->dateLastPromotion = $mem->dateJoined;
                    $mem->remarks = trim($_POST['remarks']);
                    $mem->remarksInternal = trim($_POST['remarksInternal']);
                    $mem->idMember = Snowflake::generate();
                    $mem->save();

                    // add our endorsements
                    foreach ($endorsements as $end) {
                        $me = new MemberEndorsement();
                        $me->idMember = $mem->idMember;
                        $me->idEndorsement = $end->idEndorsement;
                        $me->save();
                    }

                    $this->redirect(sprintf("/unit/%s/roster/%s",
                        Snowflake::format($this->unit->idUnit),
                        Snowflake::format($mem->idMember)));
                } else {
                    $editMember->name = trim($_POST['name']);
                    $editMember->role = trim($_POST['role'] ?? "");
                    $editMember->dateJoined = new Carbon($_POST['dateJoined']);
                    $editMember->remarks = trim($_POST['remarks']);
                    $editMember->remarksInternal = trim($_POST['remarksInternal']);
                    if ($editMember->idRank != $rank->idRank) {
                        $editMember->idRank = $rank->idRank;
                        $editMember->dateLastPromotion = Carbon::now();
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
                        Snowflake::format($this->unit->idUnit),
                        Snowflake::format($editMember->idMember)));
                }
                return true;
            }

            if ($idMember != "add") {
                // editing a member
                $this->addBreadcrumb($editMember->name, sprintf("/unit/%s/roster/%s",
                    Snowflake::format($this->unit->idUnit),
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
                    Snowflake::format($this->unit->idUnit)));

                $empty = new Member();
                $empty->idUnit = $this->unit->idUnit;
                $empty->idRank = 0;
                $empty->name = "";
                $empty->role = "";
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

    public function actionConfig()
    {
        $this->addBreadcrumb("Configuration", sprintf("/unit/%s/config",
            Snowflake::format($this->unit->idUnit)));
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
        $this->addBreadcrumb("Configuration", sprintf("/unit/%s/config",
            Snowflake::format($this->unit->idUnit)));
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

    public function actionConfigGroups()
    {
        $this->addBreadcrumb("Configuration", sprintf("/unit/%s/config",
            Snowflake::format($this->unit->idUnit)));
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
        $this->addBreadcrumb("Configuration", sprintf("/unit/%s/config",
            Snowflake::format($this->unit->idUnit)));
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