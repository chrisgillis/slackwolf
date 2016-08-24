<?php namespace Slackwolf\Game\Command;

use Slack\RealTimeClient;
use Slackwolf\Game\GameManager;
use Slackwolf\Message\Message;

/**
 * Defines the Command abstract class.
 *
 * @package Slackwolf\Game\Command
 */
abstract class Command
{

    /**
     * @var \Slackwolf\SlackRTMClient $client
     */
    protected $client;

    /**
     * @var \Slackwolf\Game\GameManager
     */
    protected $gameManager;

    /**
     * @var \Slackwolf\Message\Message
     */
    protected $message;

    /**
     * @var \Slack\string
     *   The user ID of the command executor, example: "U0H9HHZ8V"
     */
    protected $userId;

    /**
     * @var string
     *   The channel ID, example: "D0HJF2J5L".
     */
    protected $channel;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var \Slackwolf\Game\Game $game
     */
    protected $game;

    /**
     * Command constructor.
     *
     * @param RealTimeClient $client
     *   The Slack API client.
     *
     * @param GameManager $gameManager
     *   The game manager.
     *
     * @param \Slackwolf\Message\Message $message
     *   The message object.
     *
     * @param array|NULL $args
     */
    public function __construct(RealTimeClient $client, GameManager $gameManager, Message $message, array $args = null)
    {
        $this->client = $client;
        $this->gameManager = $gameManager;
        $this->message = $message;
        $this->userId = $message->getUser();
        $this->channel = $message->getChannel();
        $this->args = $args;
        $this->game = $this->gameManager->getGame($this->channel);

        echo get_called_class() . " " . $this->userId . " " . $this->channel . "\r\n";
    }

    public function init()
    {
        // TODO remove this after all command functionality is moved to subclass constructors.
    }

    /**
     * Fires the command.
     *
     * @return void
     */
    public abstract function fire();

}
