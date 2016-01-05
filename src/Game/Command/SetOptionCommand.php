<?php namespace Slackwolf\Game\Command;

use Slack\DirectMessageChannel;
use Slackwolf\Game;
use Slackwolf\Game\Formatter\OptionFormatter;

class SetOptionCommand extends Command
{
    public function init()
    {
        if (count($this->args) > 1)
        {
            //Attempt to change an option detected
            $this->gameManager->optionsManager->setOptionValue($this->args[0], $this->args[1], true);
        }
    }
    
    public function fire()
    {
        $client = $this->client;

        $help_msg =  "Options\r\n------------------------\r\n";
        $help_msg .= "To set an option use !setOption Name Value.  The valid names and values are provided below for each option. The current value is indicated in parenthesis.\r\n";
        $help_msg .= "Available Options\r\n------------------------\r\n";
        foreach($this->gameManager->optionsManager->options as $curOption)
        {
            /** @var Slackwolf\Game\Option $curOption */
            $help_msg .= OptionFormatter::format($curOption);
        }
        
        $this->client->getDMByUserId($this->userId)->then(function(DirectMessageChannel $dm) use ($client, $help_msg) {
            $client->send($help_msg, $dm);
        });
    }
}