<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\DirectMessageChannel;
use Slackwolf\Game\Game;
use Slackwolf\Game\GameState;
use Slackwolf\Game\Role;
use Zend\Loader\Exception\InvalidArgumentException;

class SeeCommand extends Command
{
    /**
     * @var Game
     */
    private $game;

    /**
     * @var string
     */
    private $gameId;

    /**
     * @var string
     */
    private $chosenUserId;

    public function init()
    {
        $client = $this->client;

        if ($this->channel[0] != 'D') {
            throw new Exception("You may only !see from a DM.");
        }

        if (count($this->args) < 2) {
            $this->client->getDMById($this->channel)
                         ->then(
                             function (DirectMessageChannel $dmc) use ($client) {
                                 $this->client->send(":warning: Not enough arguments. Usage: !see #channel @user", $dmc);
                             }
                         );

            throw new InvalidArgumentException();
        }

        $channelId   = null;
        $channelName = "";

        if (strpos($this->args[0], '#C') !== false) {
            $channelId = $this->args[0];
        } elseif (strpos($this->args[0], '#') !== false) {
            $channelName = substr($this->args[0], 1);
        } else {
            $channelName = $this->args[0];
        }

        if ($channelId == null) {
            $this->client->getChannelByName($channelName)
                         ->then(
                             function (Channel $channel) use (&$channelId) {
                                 $channelId = $channel->getId();
                             },
                             function (Exception $e) {
                                 // Do nothing
                             }
                         );
        }

        if ($channelId == null) {
            $this->client->getGroupByName($channelName)
                         ->then(
                             function (Channel $channel) use (&$channelId) {
                                 $channelId = $channel->getId();
                             },
                             function (Exception $e) {
                                 // Do nothing
                             }
                         );
        }

        if ($channelId == null) {
            $this->client->getDMById($this->channel)
                         ->then(
                             function (DirectMessageChannel $dmc) use ($client) {
                                 $this->client->send(":warning: Invalid channel specified. Usage: !see #channel @user", $dmc);
                             }
                         );
            throw new InvalidArgumentException();
        }

        $this->game   = $this->gameManager->getGame($channelId);
        $this->gameId = $channelId;

        if (!$this->game) {
            $this->client->getDMById($this->channel)
                         ->then(
                             function (DirectMessageChannel $dmc) use ($client) {
                                 $this->client->send(":warning: Could not find a running game on the specified channel.", $dmc);
                             }
                         );

            throw new InvalidArgumentException();
        }

        $this->chosenUserId = $this->args[1];

        $player = $this->game->getPlayerById($this->userId);

        if ( ! $player) {
            $this->client->getDMById($this->channel)
                 ->then(
                     function (DirectMessageChannel $dmc) use ($client) {
                         $this->client->send(":warning: Could not find you in the game you specified.", $dmc);
                     }
                 );

            throw new InvalidArgumentException();
        }

        if ($player->role != Role::SEER) {
            $this->client->getDMById($this->channel)
                 ->then(
                     function (DirectMessageChannel $dmc) use ($client) {
                         $this->client->send(":warning: You aren't a seer in the specified game.", $dmc);
                     }
                 );
            throw new Exception("Player is not the seer but is trying to see.");
        }

        if (! in_array($this->game->getState(), [GameState::FIRST_NIGHT, GameState::NIGHT])) {
            throw new Exception("Can only See at night.");
        }

        if ($this->game->seerSeen()) {
            $this->client->getDMById($this->channel)
                 ->then(
                     function (DirectMessageChannel $dmc) use ($client) {
                         $this->client->send(":warning: You may only see once each night.", $dmc);
                     }
                 );
            throw new Exception("You may only see once each night.");
        }
    }

    public function fire()
    {
        $client = $this->client;

        foreach ($this->game->getPlayers() as $player) {
            if (! strstr($this->chosenUserId, $player->getId())) {
                continue;
            }

            if ($player->role == Role::WEREWOLF) {
                $msg = "@{$player->getUsername()} is on the side of the Werewolves.";
            } else {
                $msg = "@{$player->getUsername()} is on the side of the Villagers.";
            }

            $this->client->getDMById($this->channel)
                 ->then(
                     function (DirectMessageChannel $dmc) use ($client, $msg) {
                         $this->client->send($msg, $dmc);
                     }
                 );

            $this->game->setSeerSeen(true);

            $this->gameManager->changeGameState($this->game->getId(), GameState::DAY);

            return;
        }

        $this->client->getDMById($this->channel)
             ->then(
                 function (DirectMessageChannel $dmc) use ($client) {
                     $this->client->send("Could not find the user you asked for.", $dmc);
                 }
             );
    }
}