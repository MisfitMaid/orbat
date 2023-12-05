<?php

namespace Orbat\Controller;

use Carbon\Carbon;
use Orbat\Model\Endorsement;
use Orbat\Model\Group;
use Orbat\Model\Member;
use Orbat\Model\MemberEndorsement;
use Orbat\Model\Rank;
use Orbat\Snowflake;

class UnitRoster extends UnitBase
{
    public function beforeAction($action)
    {
        $action = parent::beforeAction($action);
        $this->twig->addGlobal("activeMenu", "roster");
        $this->addBreadcrumb("Roster", sprintf("/unit/%s/roster",
            $this->unit->slug()));
        return $action;
    }

    public function actionRoster()
    {
        if ($this->canEdit) {
            if (isset($_POST['csrf']) && isset($_POST['delete'])) {
                if ($_POST['csrf'] !== \Nin\Nin::getSession('csrf_token')) {
                    $this->displayError('Invalid token.');
                    return false;
                }

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

    public function actionMemberView($idMember)
    {
        /** @var Member $member */
        $member = Member::findByPk(Snowflake::parse($idMember));
        if (!$member || $member->idUnit != $this->unit->idUnit) {
            $this->displayError("Invalid member");
            return false;
        }

        $this->dump($member);
        $this->render("base");
    }

    public function actionMemberAdd($idMember = null)
    {
        if ($this->requireEdit()) {
            if ($idMember != "add") {
                /** @var Member $editMember */
                $editMember = Member::findByPk(Snowflake::parse($idMember));
                if (!$editMember || $editMember->idUnit != $this->unit->idUnit) {
                    $this->displayError("Invalid member");
                    return false;
                }
            }

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
                $this->addBreadcrumb("Add member", sprintf("/unit/%s/roster/add",
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
        }
    }
}