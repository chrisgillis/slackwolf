<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slackwolf\Game\RoleStrategy;

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

        // Check to see that a game does not currently exist
        if ($this->gameManager->hasGame($this->channel)) {
            $this->client->getChannelGroupOrDMByID($this->channel)->then(function (ChannelInterface $channel) use ($client) {
                $client->send('A game is already in progress.', $channel);
            });

            return;
        }

        $this->client->getChannelGroupOrDMByID($this->channel)
            ->then(function (ChannelInterface $channel) {
                return $channel->getMembers();
            })
            ->then(function (array $users) use ($gameManager, $message, $client) {
                /** @var \Slack\User[] $users */
                $this->filterChosen($users);

                if(count($users) < 1) {
                    $this->client->getChannelGroupOrDMByID($this->channel)
                        ->then(function (ChannelInterface $channel) use ($client) {
                            $client->send("Cannot start a game without any users.", $channel);
                        });
                    return;
                }

                try {
                    $gameManager->newGame($message->getChannel(), $users, new RoleStrategy\Classic());
                } catch (Exception $e) {
                    $this->client->getChannelGroupOrDMByID($this->channel)->then(function (ChannelInterface $channel) use ($client,$e) {
                        $client->send($e->getMessage(), $channel);
                    });
                }
            });
    }

    /**
     * @param \Slack\User[] $users
     */
    private function filterChosen(&$users)
    {
        $chosenUsers = [];

        foreach ($this->args as $chosenUser) {
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