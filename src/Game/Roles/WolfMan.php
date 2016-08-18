<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the WolfMan class.
 *
 * @package Slackwolf\Game\Roles
 */
class WolfMan extends Werewolf
{

    /**
     * {@inheritdoc}
     */
	public function appearsAsWerewolf() {
		return false;
	}


    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::WOLFMAN;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
		return "A werewolf who appears to the Seer as a Villager.";
	}
}