<?php namespace Slackwolf\Game\Command;

use Exception;
use InvalidArgumentException;
use Slack\Channel;
use Slack\ChannelInterface;
use Slack\DirectMessageChannel;
use Slackwolf\Game\Formatter\ChannelIdFormatter;
use Slackwolf\Game\Formatter\UserIdFormatter;
use Slackwolf\Game\Game;
use Slackwolf\Game\GameState;
use Slackwolf\Game\Role;

/**
 * Defines the HealCommand class.
 */
class HealCommand extends Command
{
    /**
     * @var Game
     */
    private $game;

    public function init()
    {
        $client = $this->client;

        if ($this->channel[0] != 'D') {
            throw new Exception("You may only !heal privately.");
        }

        if (count($this->args) < 2) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: Invalid command. Usage: !heal #channel @user", $channel);
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
                                 $this->client->send(":warning: Invalid channel specified. Usage: !heal #channel @user", $dmc);
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

        $this->args[1] = UserIdFormatter::format($this->args[1], $this->game->getOriginalPlayers());
    }

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        $client = $this->client;

        if ($this->game->getState() != GameState::NIGHT) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You can only heal at night.", $channel);
                   });
            throw new Exception("Healing occurs only during the night.");
        }

        // Voter should be alive
        if ( ! $this->game->isPlayerAlive($this->userId)) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You aren't alive in the specified channel.", $channel);
                   });
            throw new Exception("Can't heal if dead.");
        }

        // Person should be Witch
        $player = $this->game->getPlayerById($this->userId);

        if (!$player->role->isRole(Role::WITCH)) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You have to be a witch to heal.", $channel);
                   });
            throw new Exception("Only witch can heal.");
        }

        // Witch should have poison potion
        if ($this->game->getWitchHealingPotion() <= 0) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You have used your healing potion.", $channel);
                   });
            throw new Exception("Witch healing potion is 0.");
        }

        if ($this->args[1] == 'noone') {
          $this->game->setWitchHealed(true);
          $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":wine_glass: You have chosen not to heal anyone tonight.", $channel);
                   });
          $this->gameManager->changeGameState($this->game->getId(), GameState::DAY);
          return true;
        }

        // Person player is voting for should be targetted by wolves
        $votes = $this->game->getVotes();

        if (count($votes) <= 0) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: Wolves have not killed anyone yet.", $channel);
                   });
            throw new Exception("Wolves have not chosen target.");
        }
        else {
          $found_target = false;
          foreach ($votes as $kill_target_id => $voters) {
            if ($this->args[1] == $kill_target_id) {
              $found_target = true;
              break;
            }
          }
          if (!$found_target) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: Wolves did not kill that person tonight.", $channel);
                   });
            throw new Exception("Wolves chosen target not healing target.");
          }
        }

        $this->game->setWitchHealingPotion(0);
        $this->game->setWitchHealedUserId($this->args[1]);
        $this->game->setWitchHealed(true);

        $client->getChannelGroupOrDMByID($this->channel)
               ->then(function (ChannelInterface $channel) use ($client) {
                   $client->send("Healing successful.", $channel);
               });

        $this->gameManager->changeGameState($this->game->getId(), GameState::DAY);
    }
}