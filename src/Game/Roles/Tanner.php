<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines thee Tanner class.
 *
 * @package Slackwolf\Game\Roles
 */
class Tanner extends Role
{

    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::TANNER;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
		return "A player not on the side of the villagers or the werewolves who wins if is lynched by the villagers.";
	}
}
