<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

class Hunter extends Role
{
    public function getName() {
        return Role::HUNTER;
    }

    public function getDescription() {
        return "A villager who can kill 1 other person if he or she is killed, during day or night.";
    }
}