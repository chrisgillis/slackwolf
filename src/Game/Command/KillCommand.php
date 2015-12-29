<?php namespace Slackwolf\Game\Command;

use Exception;
use InvalidArgumentException;
use Slack\Channel;
use Slack\ChannelInterface;
use Slack\DirectMessageChannel;
use Slackwolf\Game\Formatter\ChannelIdFormatter;
use Slackwolf\Game\Formatter\KillFormatter;
use Slackwolf\Game\Formatter\UserIdFormatter;
use Slackwolf\Game\Game;
use Slackwolf\Game\GameState;
use Slackwolf\Game\Role;

class KillCommand extends Command
{
    /**
     * @var Game
     */
    private $game;

    public function init()
    {
        $client = $this->client;

        if ($this->channel[0] != 'D') {
            throw new Exception("You may only !kill privately.");
        }

        if (count($this->args) < 2) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: Invalid command. Usage: !kill #channel @user", $channel);
                   });
            throw new InvalidArgumentException("Not enough arguments");
        }

        $this->args[1] = UserIdFormatter::format($this->args[1]);

        $client = $this->client;

        $channelId   = null;
        $channelName = "";

        if (strpos($this->args[0], '#C') !== false) {
            $channelId = ChannelIdFormatter::format($this->args[0]);
        } else {
            if (strpos($this->args[0], '#') !== false) {
                $channelName = substr($this->args[0], 1);
            } else {
                $channelName = $this->args[0];
            }
        }

        if ($channelId != null) {
            $this->client->getChannelById($channelId)
                         ->then(
                             function (ChannelInterface $channel) use (&$channelId) {
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
                             function (ChannelInterface $channel) use (&$channelId) {
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
                                 $this->client->send(":warning: Invalid channel specified. Usage: !kill #channel @user", $dmc);
                             }
                         );
            throw new InvalidArgumentException();
        }

        $this->game = $this->gameManager->getGame($channelId);

        if ( ! $this->game) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: No game in progress.", $channel);
                   });
            throw new Exception("No game in progress.");
        }
    }

    public function fire()
    {
        $client = $this->client;

        if ($this->game->getState() != GameState::NIGHT) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You can only kill at night.", $channel);
                   });
            throw new Exception("Killing occurs only during the night.");
        }

        // Voter should be alive
        if ( ! $this->game->hasPlayer($this->userId)) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You aren't alive in the specified channel.", $channel);
                   });
            throw new Exception("Can't kill if dead.");
        }

        // Person player is voting for should also be alive
        if ( ! $this->game->hasPlayer($this->args[1])) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: Could not find that player.", $channel);
                   });
            throw new Exception("Voted player not found in game.");
        }

        // Person should be werewolf
        $player = $this->game->getPlayerById($this->userId);

        if ($player->role != Role::WEREWOLF) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: YOu have to be a werewolf to kill.", $channel);
                   });
            throw new Exception("Only werewolves can kill.");
        }

        if ($this->game->hasPlayerVoted($this->userId)) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You have already voted.", $channel);
                   });
            throw new Exception("You have already voted.");
        }

        $this->game->vote($this->userId, $this->args[1]);

        $msg = KillFormatter::format($this->game);

        foreach($this->game->getPlayersOfRole(Role::WEREWOLF) as $player) {
            $client->getDMByUserID($player->getId())
                ->then(function(DirectMessageChannel $channel) use ($client,$msg) {
                    $client->send($msg,$channel);
                });
        }

        foreach ($this->game->getPlayersOfRole(Role::WEREWOLF) as $player)
        {
            if ( ! $this->game->hasPlayerVoted($player->getId())) {
                return;
            }
        }

        $votes = $this->game->getVotes();

        if (count($votes) > 1) {
            $this->game->clearVotes();
            foreach($this->game->getPlayersOfRole(Role::WEREWOLF) as $player) {
                $client->getDMByUserID($player->getId())
                       ->then(function(DirectMessageChannel $channel) use ($client) {
                           $client->send(":warning: The werewolves did not unanimously vote on a member of the town. Vote again.",$channel);
                       });
            }
            return;
        }

        $this->game->setWolvesVoted(true);

        $this->gameManager->changeGameState($this->game->getId(), GameState::DAY);
    }
}
