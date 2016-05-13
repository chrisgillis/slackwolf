<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;
class Seer extends Role
{
	public function getName() {
		return Role::SEER;
	}

	public function getDescription() {
		return "A villager who, once each night, is allowed to see the role of another player. The bot will private message you.";
	}
}