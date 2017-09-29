<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\ChannelInterface;
use Slack\DirectMessageChannel;
use Slack\RealTimeClient;
use Slackwolf\Game\Formatter\ChannelIdFormatter;
use Slackwolf\Game\Formatter\UserIdFormatter;
use Slackwolf\Game\GameManager;
use Slackwolf\Game\GameState;
use Slackwolf\Game\Role;
use Slackwolf\Message\Message;
use Zend\Loader\Exception\InvalidArgumentException;

/**
 * Defines the SeeCommand class.
 */
class SeeCommand extends Command
{

    /**
     * @var string
     */
    private $gameId;

    /**
     * @var string
     */
    private $chosenUserId;

    /**
     * {@inheritdoc}
     *
     * Constructs a new See command.
     */
    public function __construct(RealTimeClient $client, GameManager $gameManager, Message $message, array $args = null)
    {
        parent::__construct($client, $gameManager, $message, $args);

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
                                 $this->client->send(":warning: Invalid channel specified. Usage: !see #channel @user", $dmc);
                             }
                         );
            throw new InvalidArgumentException();
        }

        $this->game = $this->gameManager->getGame($channelId);
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

        $this->args[1] = UserIdFormatter::format($this->args[1], $this->game->getOriginalPlayers());
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

        // Player should be alive
        if ( ! $this->game->isPlayerAlive($this->userId)) {
            $client->getChannelGroupOrDMByID($this->channel)
                ->then(function (ChannelInterface $channel) use ($client) {
                    $client->send(":warning: You aren't alive in the specified channel.", $channel);
                });
            throw new Exception("Can't See if dead.");
        }

        if (!$player->role->isRole(Role::SEER) && !$player->role->isRole(Role::FOOL)) {
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

        if ($player->role->isRole(Role::SEER) && $this->game->seerSeen()) {
            $this->client->getDMById($this->channel)
                 ->then(
                     function (DirectMessageChannel $dmc) use ($client) {
                         $this->client->send(":warning: You may only see once each night.", $dmc);
                     }
                 );
            throw new Exception("You may only see once each night.");
        }

	if ($player->role->isRole(Role::FOOL) && $this->game->foolSeen()) {
            $this->client->getDMById($this->channel)
                 ->then(
                     function (DirectMessageChannel $dmc) use ($client) {
                         $this->client->send(":warning: You may only see once each night.", $dmc);
                     }
                 );
            throw new Exception("You may only see once each night.");
        }

    }

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        $client = $this->client;
        $currentPlayer = $this->game->getPlayerById($this->userId);
        
        foreach ($this->game->getLivingPlayers() as $player) {
            if (! strstr($this->chosenUserId, $player->getId())) {
                continue;
            }

            if ($currentPlayer->role->isRole(Role::FOOL)) {
                $probs = rand(1, 10);echo ("Probs : $probs\n");
                if($probs >= 4) {
                    $appearsAsWerewolf = !$player->role->appearsAsWerewolf();
                }
                else {
                    $appearsAsWerewolf = $player->role->appearsAsWerewolf();
                }
            }
            else {
                $appearsAsWerewolf = $player->role->appearsAsWerewolf();
            }

            if ($appearsAsWerewolf) {
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

            if ($currentPlayer->role->isRole(Role::SEER)) {
                 $this->game->setSeerSeen(true);
            }
            else {
                 $this->game->setFoolSeen(true);
            }

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
