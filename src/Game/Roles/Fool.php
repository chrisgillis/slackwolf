<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the Fool class.
 *
 * @package Slackwolf\Game\Roles
 */
class Fool extends Role
{

    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::FOOL;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
		return "A villager who, once each night, is allowed to see the role of another player. The bot will private message you.";
	}
}
