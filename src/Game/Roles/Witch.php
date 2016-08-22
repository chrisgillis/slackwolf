<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the Witch class.
 *
 * @package Slackwolf\Game\Roles
 */
class Witch extends Role
{

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return Role::WITCH;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription() {
        return "A villager who has 1 healing potion and 1 poison potion and may heal and/or kill targets at night, but can only do each action once per game.";
    }
}