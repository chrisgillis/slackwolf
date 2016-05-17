<?php namespace Slackwolf\Game;
use Slackwolf\Game\Roles\Seer;
use Slackwolf\Game\Roles\Tanner;
use Slackwolf\Game\Roles\Lycan;
use Slackwolf\Game\Roles\Beholder;
use Slackwolf\Game\Roles\Bodyguard;
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
    const BODYGUARD = "Bodyguard";
    const TANNER = "Tanner";
    const LYCAN = "Lycan";
    const BEHOLDER = "Beholder";
    const WITCH = "Witch";
    const WOLFMAN = "Wolf Man";

    public static function getSpecialRoles() {
    	return [new Seer(), new Tanner(), new Lycan(), new Beholder(), new Bodyguard(), new WolfMan()];
    }
}