<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;
class Lycan extends Role
{
	public function appearsAsWerewolf() {
		return true;
	}

	public function isWerewolfTeam() {
		return false;
	}

	public function getName() {
		return "Lycan";
	}

	public function getDescription() {
		return "A villager who appears to the Seer as a Werewolf.";
	}
}