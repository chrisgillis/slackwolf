<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the Lycan class.
 *
 * @package Slackwolf\Game\Roles
 */
class Lycan extends Role
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
		return false;
	}

    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::LYCAN;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
		return "A villager who appears to the Seer as a Werewolf.";
	}
}