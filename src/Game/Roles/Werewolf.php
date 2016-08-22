<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the Werewolf class.
 *
 * @package Slackwolf\Game\Roles
 */
class Werewolf extends Role
{

    /**
     * {@inheritdoc}
     */
	public function appearsAsWerewolf() {
		return true;
	}

    /**
     * {@inheritdoc}
     */
	public function isWerewolfTeam() {
		return true;
	}

    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::WEREWOLF;
	}
}