<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;
class Villager extends Role
{
	public function getName() {
		return Role::VILLAGER;
	}
}