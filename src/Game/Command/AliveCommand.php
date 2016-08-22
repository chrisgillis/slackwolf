<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slackwolf\Game\Formatter\PlayerListFormatter;
use Slackwolf\Game\Game;

/**
 * Defines the AliveCommand class.
 *
 * @package Slackwolf\Game\Command
 */
class AliveCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        if ( ! $this->gameManager->hasGame($this->channel)) {
            $this->client->getChannelGroupOrDMByID($this->channel)
               ->then(function (ChannelInterface $channel) {
                   $this->client->send(":warning: No game in progress.", $channel);
               });
            return;
        }

        // build list of players
        $playersList = PlayerListFormatter::format($this->game->getLivingPlayers());
        $this->gameManager->sendMessageToChannel($this->game, ":ok: Players still alive: " . $playersList);

    }
}