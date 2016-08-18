<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slackwolf\Game\Formatter\PlayerListFormatter;
use Slackwolf\Game\Game;

/**
 * Defines the DeadCommand class.
 */
class DeadCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        $client = $this->client;

        if ( ! $this->gameManager->hasGame($this->channel)) {
            $client->getChannelGroupOrDMByID($this->channel)
               ->then(function (ChannelInterface $channel) use ($client) {
                   $client->send(":warning: No game in progress.", $channel);
               });
            return;
        }

        // build list of players
        $playersList = PlayerListFormatter::format($this->game->getDeadPlayers());
        if (empty($playersList))
        {
            $this->gameManager->sendMessageToChannel($this->game, "No one has died yet.");
        }
        else
        {
            $this->gameManager->sendMessageToChannel($this->game, ":angel: Players who have died: ".$playersList);
        }
    }
}