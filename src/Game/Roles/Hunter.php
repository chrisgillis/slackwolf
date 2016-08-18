<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the Hunter class.
 *
 * @package Slackwolf\Game\Roles
 */
class Hunter extends Role
{

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return Role::HUNTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription() {
        return "A villager who can kill 1 other person if he or she is killed, during day or night.";
    }
}