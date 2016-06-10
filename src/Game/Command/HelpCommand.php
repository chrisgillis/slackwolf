<?php namespace Slackwolf\Game\Command;

use Slack\Channel;
use Slack\ChannelInterface;
use Slack\DirectMessageChannel;
use Slackwolf\Game\Role;

class HelpCommand extends Command
{
    public function fire()
    {
        $client = $this->client;

        $help_msg =  "How to Play #Werewolf\r\n------------------------\r\n";
        $help_msg .= "Werewolf is a party game of social deduction. Players are private messaged their role when the game begins. ";
        $help_msg .= "If you are a Villager, you must find out who the werewolves are based on their voting and your social deduction skills. ";
        $help_msg .= "if you are a Werewolf, you must pretend you are not a werewolf by lying as best as you can.\r\n";
        $help_msg .= "The game takes place over several Days and Nights. Each Day all players vote on a player to lynch. The player with the most votes is lynched. If there is a tie, the tied players are lynched. ";
        $help_msg .= "Each night, the werewolves will be allowed to vote privately on one player to kill. The decision must be unanimous. If its not, you'll keep voting until it is. The bot will private message you.\r\n";
        $help_msg .= "The villagers win if they eliminate all the werewolves. The werewolves win if they equal or outnumber the remaining players.\r\n\r\n";
        $help_msg .= "Special Roles\r\n------------------------\r\n";

        foreach(Role::getSpecialRoles() as $specialRole) {
            $help_msg .= " |_ " . $specialRole->getName() . " - " . $specialRole->getDescription() . "\r\n";
        }
        $help_msg .= "\r\n";

        $help_msg .= "Available Commands\r\n------------------------\r\n";
        $help_msg .= "|_  !new - Create a new lobby for players to !join for the next game\r\n";
        $help_msg .= "|_  !join - Join the lobby for the next game\r\n";
        $help_msg .= "|_  !leave - Leave the lobby for the next game\r\n";
        $help_msg .= "|_  !start - Start the game, when called with no parameters the lobby players are used\r\n";
        $help_msg .= "|_  !start all - Starts a new game with everyone in the channel participating\r\n";
        $help_msg .= "|_  !start @user1 @user2 @user3 - Starts a new game with the three specified users participating\r\n";
        $help_msg .= "|_  !vote @user1|noone|clear - During the day, Vote for a @player, no one (no lynch), or clear your existing vote (changevote option must be enabled).\r\n";
        $help_msg .= "|_  !see #channel @user1 -  Seer only. As the seer, find out if user is villager or werewolf. #channel is the name of the channel you're playing in\r\n";
        $help_msg .= "|_  !kill #channel @user1 - Werewolf only. As a werewolf, in a PM to the bot, you can vote to kill a user each night. Must be unanimous amongst all werewolves.\r\n";
        $help_msg .= "|_  !poison #channel @user1 - Witch only. During night time, the witch can choose to use her poison potion to kill a target player once per game.\r\n";
        $help_msg .= "|_  !heal #channel @user1 - Witch only. During night time, the witch can choose to use her healing potion to revive a targeted player once per game.\r\n";
        $help_msg .= "|_  !guard #channel @user1 - Bodyguard only. The bodyguard can protect a player from being eliminated once each night. Cant select the same user two nights in a row.\r\n";
        $help_msg .= "|_  !shoot #channel @user1 - Hunter only. The hunter can shoot and kill another player if they are killed during day or night.\r\n";
        $help_msg .= "|_  !end - Cause the game to end prematurely\r\n";
        $help_msg .= "|_  !option - View or change options.  Use without any parameters for help and current values.\r\n";
        $help_msg .= "|_  !dead - Show dead players\r\n";
        $help_msg .= "|_  !alive - Show living players\r\n";
        $help_msg .= "|_  !status - Show game status\r\n";

        $this->client->getDMByUserId($this->userId)->then(function(DirectMessageChannel $dm) use ($client, $help_msg) {
            $client->send($help_msg, $dm);
        });

        if ($this->channel[0] != 'D') {
            $client->getChannelGroupOrDMByID($this->channel)
               ->then(function (ChannelInterface $channel) use ($client) {
                   $client->send(":book: Please check your Direct Messages for help text.", $channel);
               });
        }
    }
}