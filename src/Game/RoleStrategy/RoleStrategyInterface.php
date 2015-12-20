<?php namespace Slackwolf\Game\RoleStrategy;

interface RoleStrategyInterface
{
    /**
     * @param array \Slack\User[]
     *
     * @return \Slack\User[]
     */
    public function assign(array $users);
}