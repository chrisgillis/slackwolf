<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;
class Beholder extends Role
{
	public function getName() {
		return Role::BEHOLDER;
	}

	public function getDescription() {
		return "A villager who learns who the Seer is on the first night.";
	}
}