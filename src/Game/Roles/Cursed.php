<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the WolfMan class.
 *
 * @package Slackwolf\Game\Roles
 */
class Cursed extends Werewolf
{
	private $isAlive = true;
    /**
     * {@inheritdoc}
     */
	public function appearsAsWerewolf() {
		return !$this->isAlive;
	}

    /**
     * {@inheritdoc}
     */
	public function isWerewolfTeam() {
		return !$this->isAlive;
	}

    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::CURSED;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
		return "A cursed child who will turn into a werewolf when bitten by werewolves.";
	}

	public function kill() {
		$this->isAlive = false;
	}
}
