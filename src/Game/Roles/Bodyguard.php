<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;
class Bodyguard extends Role
{
	public function getName() {
		return "Bodyguard";
	}

	public function getDescription() {
		return "A villager who may protect a player from being eliminated once each night, but not the same person two nights in a row.";
	}
}