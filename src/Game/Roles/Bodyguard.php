<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the  Bodyguard class.
 *
 * @package Slackwolf\Game\Roles
 */
class Bodyguard extends Role
{

    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::BODYGUARD;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
		return "A villager who may protect a player from being eliminated once each night, but not the same person two nights in a row.";
	}
}