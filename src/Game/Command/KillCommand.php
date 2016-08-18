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
use Slackwolf\Game\OptionManager;
use Slackwolf\Game\OptionName;

/**
 * Defines the KillCommand class.
 */
class KillCommand extends Command
{

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

        if ( ! $this->game) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: No game in progress.", $channel);
                   });
            throw new Exception("No game in progress.");
        }

        $this->args[1] = UserIdFormatter::format($this->args[1], $this->game->getOriginalPlayers());
    }

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        $client = $this->client;
        if ($this->game->getWolvesVoted()){
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: Wolves have already voted.", $channel);
                   });
            throw new Exception("Wolves can't vote after voting ends.");
        }

        if ($this->game->getState() != GameState::NIGHT) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You can only kill at night.", $channel);
                   });
            throw new Exception("Killing occurs only during the night.");
        }

        // Voter should be alive
        if ( ! $this->game->isPlayerAlive($this->userId)) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You aren't alive in the specified channel.", $channel);
                   });
            throw new Exception("Can't kill if dead.");
        }

        // Person player is voting for should also be alive
        if ( ! $this->game->isPlayerAlive($this->args[1])) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: Could not find that player.", $channel);
                   });
            throw new Exception("Voted player not found in game.");
        }

        // Person should be werewolf
        $player = $this->game->getPlayerById($this->userId);

        if (!$player->role->isWerewolfTeam()) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You have to be a werewolf to kill.", $channel);
                   });
            throw new Exception("Only werewolves can kill.");
        }

        if ($this->game->hasPlayerVoted($this->userId)) {
            //If changeVote is not enabled and player has already voted, do not allow another vote
            if (!$this->gameManager->optionsManager->getOptionValue(OptionName::changevote))
            {
                throw new Exception("Vote change not allowed.");
            }

            $this->game->clearPlayerVote($this->userId);
        }

        $this->game->vote($this->userId, $this->args[1]);

        $msg = KillFormatter::format($this->game);

        foreach($this->game->getWerewolves() as $player) {
            $client->getDMByUserID($player->getId())
                ->then(function(DirectMessageChannel $channel) use ($client,$msg) {
                    $client->send($msg,$channel);
                });
        }

        foreach ($this->game->getWerewolves() as $player)
        {
            if ( ! $this->game->hasPlayerVoted($player->getId())) {
                return;
            }
        }

        $votes = $this->game->getVotes();

        if (count($votes) > 1) {
            $this->game->clearVotes();
            foreach($this->game->getWerewolves() as $player) {
                $client->getDMByUserID($player->getId())
                       ->then(function(DirectMessageChannel $channel) use ($client) {
                           $client->send(":warning: The werewolves did not unanimously vote on a member of the town. Vote again.",$channel);
                       });
            }
            return;
        }

        $this->game->setWolvesVoted(true);

        // send heal message to witch if in game
        $witches = $this->game->getPlayersOfRole(Role::WITCH);
        if (count($witches) > 0) {
            if ($this->game->getWitchHealingPotion() > 0) {
                foreach($witches as $player) {

                    $killed_player = $this->game->getPlayerById($this->args[1]);
                    $witch_msg = ":wine_glass: @{$killed_player->getUsername()} was attacked, would you like to heal that person?  Type \"!heal #channel @user\" to save that person \r\nor \"!heal #channel noone\" to let that person die.  \r\n:warning: Night will not end until you make a decision.";

                    $client->getDMByUserID($player->getId())
                        ->then(function(DirectMessageChannel $channel) use ($client,$witch_msg) {
                            $client->send($witch_msg,$channel);
                        });
                }
            }
            else {
                $this->game->setWitchHealed(true);
            }
        }

        $this->gameManager->changeGameState($this->game->getId(), GameState::DAY);
    }
}
