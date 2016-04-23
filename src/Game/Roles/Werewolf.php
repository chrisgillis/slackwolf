<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;
class Werewolf extends Role
{
	public function appearsAsWerewolf() {
		return true;
	}

	public function isWerewolfTeam() {
		return true;
	}

	public function getName() {
		return Role::WEREWOLF;
	}
}