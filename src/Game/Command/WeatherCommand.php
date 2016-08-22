<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slackwolf\Game\Formatter\GameStatusFormatter;
use Slackwolf\Game\Game;

/**
 * Defines the WeatherCommand class.
 */
class WeatherCommand extends Command
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
                   $client->send(":warning: Run this command in the game channel.", $channel);
               });
            return;
        }

          $this->gameManager->sendMessageToChannel($this->game, ":rain_cloud: It is raining. It is a cold rain, and the freezing drops chill you to the bone." );

    }
}
