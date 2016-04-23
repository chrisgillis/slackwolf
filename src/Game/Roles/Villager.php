<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;
class Villager extends Role
{
	public string getName() {
		return Role::VILLAGER;
	}
}