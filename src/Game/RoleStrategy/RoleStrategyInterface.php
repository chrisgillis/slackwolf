<?php namespace Slackwolf\Game\RoleStrategy;

/**
 * Defines the RoleStrategy interface.
 *
 * @package Slackwolf\Game\RoleStrategy
 */
interface RoleStrategyInterface
{
    /**
     * @param \Slack\User[] $users
     * @param \Slackwolf\Game\OptionsManager $optionsManager
     *
     * @return \Slack\User[]
     */
    public function assign(array $users, $optionsManager);

    /**
     * @return string
     */
    public function getRoleListMsg();

    /**
     * @param $gameManager
     * @param $game
     * @param $msg
     */
    public function firstNight($gameManager, $game, $msg);
}