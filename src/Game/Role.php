<?php namespace Slackwolf\Game;
use Slackwolf\Game\Roles\Seer;
use Slackwolf\Game\Roles\Tanner;
use Slackwolf\Game\Roles\Lycan;
use Slackwolf\Game\Roles\Beholder;
use Slackwolf\Game\Roles\Bodyguard;
use Slackwolf\Game\Roles\Witch;
use Slackwolf\Game\Roles\WolfMan;

class Role
{
	public function appearsAsWerewolf() {
		return false;
	}

	public function isWerewolfTeam() {
		return false;
	}

	public function getName() {
		return null;
	}

	public function getDescription() {
		return null;
	}

	public function isRole($roleName) {
		return $roleName == $this->getName();
	}

    const VILLAGER = "Villager";
    const SEER = "Seer";
    const WEREWOLF = "Werewolf";

    const BEHOLDER = "Beholder";
    const BODYGUARD = "Bodyguard";
    const HUNTER = "Hunter";
    const LYCAN = "Lycan";
    const TANNER = "Tanner";
    const WITCH = "Witch";
    const WOLFMAN = "Wolf Man";

    public static function getSpecialRoles() {
    	return [
            new Beholder(),
            new Bodyguard(),
            new Hunter(),
            new Lycan(),
            new Seer(),
            new Tanner(),
            new Witch(),
            new WolfMan()
        ];
    }
}