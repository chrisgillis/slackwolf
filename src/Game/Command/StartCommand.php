<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slackwolf\Game\Formatter\UserIdFormatter;
use Slackwolf\Game\RoleStrategy;
use Slackwolf\Game\GameState;

class StartCommand extends Command
{
    public function init()
    {
        if ($this->channel[0] == 'D') {
            throw new Exception("Can't start a game by direct message.");
        }
    }

    public function fire()
    {
        $client = $this->client;
        $gameManager = $this->gameManager;
        $message = $this->message;
        /** @var Game $game */
        $game;
        
        $loadPlayers = true;
        // Check to see that a game does not currently exist
        if ($this->gameManager->hasGame($this->channel)) {
            $game = $this->gameManager->getGame($this->channel);
            if ($game->getState() == GameState::LOBBY){    
                $loadPlayers = false;
            } else {
                $this->client->getChannelGroupOrDMByID($this->channel)->then(function (ChannelInterface $channel) use ($client) {
                    $client->send('A game is already in progress.', $channel);
                });

                return;
            }
        }

        if ($loadPlayers) {
            $this->client->getChannelGroupOrDMByID($this->channel)
                ->then(function (Channel $channel) {
                    return $channel->getMembers();
                })
                ->then(function (array $users) use ($gameManager, $message, $client) {
                    /** @var \Slack\User[] $users */
                    $this->filterChosen($users);

                    if(count($users) < 3) {
                        $this->client->getChannelGroupOrDMByID($this->channel)
                            ->then(function (ChannelInterface $channel) use ($client) {
                                $client->send("Cannot start a game with less than 3 players.", $channel);
                            });
                        return;
                    }

                    try {
                        $gameManager->newGame($message->getChannel(), $users, new RoleStrategy\Classic());
                        //$game = $gameManager->getGame($message->getChannel());
                    } catch (Exception $e) {
                        $this->client->getChannelGroupOrDMByID($this->channel)->then(function (ChannelInterface $channel) use ($client,$e) {
                            $client->send($e->getMessage(), $channel);
                        });
                    }
                });
        }
        $gameManager->startGame($message->getChannel());
    }

    /**
     * @param \Slack\User[] $users
     */
    private function filterChosen(&$users)
    {
        $chosenUsers = [];

        foreach ($this->args as $chosenUser) {
            $chosenUser = UserIdFormatter::format($chosenUser, $users);
            $chosenUsers[$chosenUser] = $chosenUser;
        }

        // Remove the bot from the player list
        foreach ($users as $key => $user) {
            if ($user->getUsername() == getenv('BOT_NAME')) {
                unset($users[$key]);
            }
        }

        // Remove players that weren't specified, if there were specified players
        if (count($chosenUsers) > 0) {
            foreach ($users as $key => $user) {
                $userFound = false;

                foreach ($chosenUsers as $chosenUser) {
                    if (strpos($chosenUser, $user->getId()) !== false) {
                        $userFound = true;
                    }
                }

                if ( ! $userFound) {
                    unset($users[$key]);
                }
            }
        }
    }
}