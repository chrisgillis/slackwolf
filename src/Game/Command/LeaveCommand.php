<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slackwolf\Game\GameState;
use Slackwolf\Game\Formatter\PlayerListFormatter;

/**
 * Defines the LeaveCommand class.
 */
class LeaveCommand extends Command
{

    public function init()
    {
        if ($this->channel[0] == 'D') {
            throw new Exception("Can't leave a game or lobby by direct message.");
        }

        if ( ! $this->game) {
            throw new Exception("No game in progress.");
        }
        
        if ($this->game->getState() != GameState::LOBBY) { 
            throw new Exception("Game in progress is not in lobby state.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        $this->game->removeLobbyPlayer($this->userId);
            
        $playersList = PlayerListFormatter::format($this->game->getLobbyPlayers());

        if ($playersList) {
            $this->gameManager->sendMessageToChannel($this->game, "Current lobby: " . $playersList);
        } else {
            $this->gameManager->sendMessageToChannel($this->game, "Lobby is now empty");
        }
    }
}