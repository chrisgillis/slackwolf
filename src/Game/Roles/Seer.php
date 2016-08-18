<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the Seer class.
 *
 * @package Slackwolf\Game\Roles
 */
class Seer extends Role
{

    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::SEER;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
		return "A villager who, once each night, is allowed to see the role of another player. The bot will private message you.";
	}
}