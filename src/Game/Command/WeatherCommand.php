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

        $weather= $this->game->weather;
        if ($weather!= null){
          if($weather==1){
            $this->gameManager->sendMessageToChannel($this->game, ":rain_cloud: It is raining. It is a cold rain, and the freezing drops chill you to the bone." );          
          }
          // Cloudy
          else if($weather==2){
            $this->gameManager->sendMessageToChannel($this->game, ":cloud: The cloud embrace the sky and cover the sun letting only a few glimmer of light");   
          }
          // Sunny
          else{
            $this->gameManager->sendMessageToChannel($this->game, ":sunny: The warm sun is shining. Its brightness almost blinds you. You take a moment to appreciate its embrace."); 
          }
        }
        else{
          $this->gameManager->sendMessageToChannel($this->game,"No Game Running"); 
        }
    }
}
