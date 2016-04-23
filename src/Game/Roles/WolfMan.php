<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;
class WolfMan extends Werewolf
{
	public function appearsAsWerewolf() {
		return false;
	}

	public function getName() {
		return Role::WOLFMAN;
	}

	public function getDescription() {
		return "A werewolf who appears to the Seer as a Villager.";
	}
}