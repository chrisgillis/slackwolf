<?php namespace Slackwolf\Game\Command;

use Exception;
use Slackwolf\Game\Formatter\UserIdFormatter;
use Slackwolf\Game\GameState;
use Zend\Loader\Exception\InvalidArgumentException;

/**
 * Defines the VoteCommand class.
 */
class VoteCommand extends Command
{

    public function init()
    {
        if ($this->channel[0] == 'D') {
            throw new Exception("You may not !vote privately.");
        }

        if (count($this->args) < 1) {
            throw new InvalidArgumentException("Must specify a player");
        }

        if ( ! $this->game) {
            throw new Exception("No game in progress.");
        }

        if ($this->game->getState() != GameState::DAY) {
            throw new Exception("Voting occurs only during the day.");
        }

        // Voter should be alive
        if ( ! $this->game->isPlayerAlive($this->userId)) {
            throw new Exception("Can't vote if dead.");
        }

        $this->args[0] = UserIdFormatter::format($this->args[0], $this->game->getOriginalPlayers());

        // Person player is voting for should also be alive
        if ( ! $this->game->isPlayerAlive($this->args[0])
                && $this->args[0] != 'noone'
                && $this->args[0] != 'clear') {
            echo 'not found';
            throw new Exception("Voted player not found in game.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        $this->gameManager->vote($this->game, $this->userId, $this->args[0]);
    }
}