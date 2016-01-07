<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slackwolf\Game\GameState;
use Slackwolf\Game\Formatter\PlayerListFormatter;

class LeaveCommand extends Command
{
    private $game;

    public function init()
    {
        if ($this->channel[0] == 'D') {
            throw new Exception("Can't leave a game or lobby by direct message.");
        }

        $this->game = $this->gameManager->getGame($this->channel);

        if ( ! $this->game) {
            throw new Exception("No game in progress.");
        }
        
        if ($this->game->getState() != GameState::LOBBY) { 
            throw new Exception("Game in progress is not in lobby state.");
        }
    }

    public function fire()
    {
        $this->game->removeLobbyPlayer($this->userId);
            
        $playersList = PlayerListFormatter::format($this->game->getLobbyPlayers());
        $this->gameManager->sendMessageToChannel($this->game, "Current lobby: ".$playersList);    
    }
}