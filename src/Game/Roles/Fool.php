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
		return "Same role as the Seer, but the information given is only correct 30% of the time. The Fool also does not know he is not the (real) Seer. Only Beholder knows who the real Seer is.";
	}
}
