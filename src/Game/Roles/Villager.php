<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the Villager class.
 *
 * @package Slackwolf\Game\Roles
 */
class Villager extends Role
{
    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::VILLAGER;
	}
}