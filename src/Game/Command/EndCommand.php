<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slack\RealTimeClient;
use Slackwolf\Game\Formatter\PlayerListFormatter;
use Slackwolf\Game\GameManager;
use Slackwolf\Message\Message;

/**
 * Defines the EndCommand class.
 */
class EndCommand extends Command
{


    /**
     * {@inheritdoc}
     *
     * Constructs a new End command.
     */
    public function __construct(RealTimeClient $client, GameManager $gameManager, Message $message, array $args = null)
    {
        parent::__construct($client, $gameManager, $message, $args);

        if ($this->channel[0] == 'D') {
            throw new Exception("Can't start a game by direct message.");
        }
    }

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

        $this->gameManager->endGame($this->channel, $this->message->getUser());
    }
}