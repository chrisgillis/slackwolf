<?php namespace Slackwolf\Game\Command;

use Slack\RealTimeClient;
use Slackwolf\Game\GameManager;
use Slackwolf\Message\Message;

abstract class Command
{
    protected $client;
    protected $gameManager;
    protected $message;
    protected $userId;
    protected $channel;
    protected $args;

    public function __construct(RealTimeClient $client, GameManager $gameManager, Message $message, array $args = null)
    {
        $this->client = $client;
        $this->gameManager = $gameManager;
        $this->message = $message;
        $this->userId = $message->getUser();
        $this->channel = $message->getChannel();
        $this->args = $args;

        $this->init();

        echo get_called_class()." ".$this->userId." ".$this->channel."\r\n";
    }

    public function init()
    {

    }

    public abstract function fire();

}