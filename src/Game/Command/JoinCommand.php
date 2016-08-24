<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slack\RealTimeClient;
use Slackwolf\Game\GameManager;
use Slackwolf\Game\GameState;
use Slackwolf\Game\Formatter\PlayerListFormatter;
use Slackwolf\Game\Formatter\UserIdFormatter;
use Slackwolf\Message\Message;

/**
 * Defines the JoinCommand class.
 */
class JoinCommand extends Command
{

    /**
     * {@inheritdoc}
     *
     * Constructs a new Join command.
     */
    public function __construct(RealTimeClient $client, GameManager $gameManager, Message $message, array $args = null)
    {
        parent::__construct($client, $gameManager, $message, $args);

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
                    /* @var $user \Slack\User */
                    if ($user->getId() == $this->userId) {
                        if ($this->game->addLobbyPlayer($user)) {
                            $playersList = PlayerListFormatter::format($this->game->getLobbyPlayers());
                            $this->gameManager->sendMessageToChannel($this->game, "Current lobby: " . $playersList);
                        } else {
                            $this->gameManager->sendMessageToChannel($this->game, "You've already joined, " . $user->getFirstName() . ". Stop trying to spam everyone.");
                        }
                    }
                }
            });
    }
}