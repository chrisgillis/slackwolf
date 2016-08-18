<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slackwolf\Game\GameState;
use Slackwolf\Game\RoleStrategy;
use Slackwolf\Game\Formatter\PlayerListFormatter;

/**
 * Defines the NewCommand class.
 */
class NewCommand extends Command
{
    public function init()
    {
        if ($this->channel[0] == 'D') {
            throw new Exception("Can't initiate a new game lobby by direct message.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        $client = $this->client;
        $gameManager = $this->gameManager;
        $message = $this->message;
        
        $loadPlayers = true;
        // Check to see that a game does not currently exist
        if ($this->gameManager->hasGame($this->channel)) {
            $this->client->getChannelGroupOrDMByID($this->channel)->then(function (ChannelInterface $channel) use ($client, $gameManager) {
                $game = $gameManager->getGame($this->channel);
                if ($game->getState == GameState::LOBBY) {
                    $client->send('A game lobby is already open.  Type !join to play the next game.', $channel);
                }
                else {
                    $client->send('A game is already in progress.', $channel);
                }
            });

            return;
        }

        try {
            $gameManager->newGame($message->getChannel(), [], new RoleStrategy\Classic());        
            $game = $gameManager->getGame($message->getChannel());
            $this->gameManager->sendMessageToChannel($game, "A new game lobby has been created.  Type !join to play the next game.");
            $userId = $this->userId;

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

            $playersList = PlayerListFormatter::format($game->getLobbyPlayers());
            $this->gameManager->sendMessageToChannel($game, "Current lobby: ".$playersList);
        } catch (Exception $e) {
            $this->client->getChannelGroupOrDMByID($this->channel)->then(function (ChannelInterface $channel) use ($client,$e) {
                $client->send($e->getMessage(), $channel);
            });
        }        
    }
}