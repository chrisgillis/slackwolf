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

class ShootCommand extends Command
{
    /**
     * @var Game
     */
    private $game;

    public function init()
    {
        $client = $this->client;

        if (count($this->args) < 2) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: Invalid command. Usage: !shoot #channel @user", $channel);
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
                                 $this->client->send(":warning: Invalid channel specified. Usage: !poison #channel @user", $dmc);
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

    public function fire()
    {
        $client = $this->client;

        // Person should be hunter
        $player = $this->game->getPlayerById($this->userId);

        if (!$player->role->isRole(Role::HUNTER)) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You have to be a hunter to shoot.", $channel);
                   });
            throw new Exception("Only hunter can shoot.");
        }

        // Hunter should be dead to shoot
        if ( $this->game->isPlayerAlive($this->userId)) {
            $client->getChannelGroupOrDMByID($this->channel)
                   ->then(function (ChannelInterface $channel) use ($client) {
                       $client->send(":warning: You can only shoot someone when dying.", $channel);
                   });
            throw new Exception("Can't shoot if alive.");
        }

        if ($this->args[1] == 'noone') {
          $client->getChannelGroupOrDMByID($this->channel)
            ->then(function (ChannelInterface $channel) use ($client) {
               $client->send(":bow_and_arrow: " . $player->getUsername() .
                  " (Hunter) decided not to shoot anyone, and died.", $channel);
            });
        }
        else {

          $targeted_player_id = $this->args[1];

          // Person player is shooting should be alive
          if ( ! $this->game->isPlayerAlive($targeted_player_id)) {
              $client->getChannelGroupOrDMByID($this->channel)
                     ->then(function (ChannelInterface $channel) use ($client) {
                         $client->send(":warning: Player is not in game or dead.", $channel);
                     });
              throw new Exception("Voted player not found in game.");
          }

          $targeted_player = $this->game->getPlayerById($targeted_player_id);
          $this->game->killPlayer($targeted_player_id);
          $this->game->setHunterNeedsToShoot(false);

          $client->getChannelGroupOrDMByID($this->channel)
               ->then(function (ChannelInterface $channel) use ($client, $player, $targeted_player) {
                   $client->send(":bow_and_arrow: " . $player->getUsername() . " (Hunter) shot dead "
                      . $targeted_player->getUsername() . ", and then died.", $channel);
               });
        }

        if ($this->game->getState() == GameState::DAY) {
          $this->gameManager->changeGameState($this->game->getId(), GameState::NIGHT);
        }
        else {
          $skipNightEnd = true;
          $this->gameManager->changeGameState($this->game->getId(), GameState::DAY, $skipNightEnd);
        }
    }
}
