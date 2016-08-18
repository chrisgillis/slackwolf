<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slackwolf\Game\GameState;
use Slackwolf\Game\Formatter\PlayerListFormatter;
use Slackwolf\Game\Formatter\UserIdFormatter;

/**
 * Defines the JoinCommand class.
 */
class JoinCommand extends Command
{
    public function init()
    {
        if ($this->channel[0] == 'D') {
            throw new Exception("Can't join a game lobby by direct message.");
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
        $userId = $this->userId;
        $game = $this->game;
    
        $this->client->getChannelGroupOrDMByID($this->channel)
            ->then(function (Channel $channel) {
                return $channel->getMembers();
            })
            ->then(function (array $users) use ($userId, $game) {
                foreach($users as $key => $user) {
                    if ($user->getId() == $userId) {
                        $game->addLobbyPlayer($user);
                    }
                }
            });
            
        $playersList = PlayerListFormatter::format($this->game->getLobbyPlayers());
        $this->gameManager->sendMessageToChannel($this->game, "Current lobby: ".$playersList);
    }
}