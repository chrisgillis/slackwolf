<?php namespace Slackwolf\Game\Command;

use Slack\DirectMessageChannel;
use Slack\RealTimeClient;
use Slackwolf\Game\GameManager;
use Slackwolf\Game\Formatter\OptionFormatter;
use Slackwolf\Message\Message;

/**
 * Defines the SetOptionCommand class.
 */
class SetOptionCommand extends Command
{

    /**
     * {@inheritdoc}
     *
     * Constructs a new SetOption command.
     */
    public function __construct(RealTimeClient $client, GameManager $gameManager, Message $message, array $args = null)
    {
        parent::__construct($client, $gameManager, $message, $args);
    }

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        if (count($this->args) > 1) {
            //Attempt to change an option detected
            $help_msg = $this->gameManager->optionsManager->setOptionValue($this->args, true);
        } else {
            $help_msg = "Options\r\n------------------------\r\n";
            $help_msg .= "Usage: !option name value\r\nThe valid names and values are provided below for each option. The current value is indicated in parenthesis.\r\n";
            $help_msg .= "Available Options\r\n------------------------\r\n";
            foreach ($this->gameManager->optionsManager->options as $curOption) {
                /* @var \Slackwolf\Game\Option $curOption */
                $help_msg .= OptionFormatter::format($curOption);
            }
        }
        $client = $this->client;
        $this->client->getDMByUserId($this->userId)->then(function (DirectMessageChannel $dm) use ($client, $help_msg) {
            $client->send($help_msg, $dm);
        });
    }
}