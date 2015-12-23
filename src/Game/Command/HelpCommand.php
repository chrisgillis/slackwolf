<?php namespace Slackwolf\Game\Command;

use Slack\DirectMessageChannel;

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
        $help_msg .= " |_ Seer - A villager who, once each night, is allowed to see the role of another player. The bot will private message you.\r\n";
        $help_msg .= " |_ Tanner - A player not on the side of the villagers or the werewolves who wins if is killed.\r\n";
        $help_msg .= " |_ Lycan - A villager who appears to the Seer as a Werewolf.\r\n";
        $help_msg .= " |_ Beholder - A villager who learns who the Seer is on the first night\r\n";
        $help_msg .= " |_ Bodyguard - A villager who may protect a player from being eliminated once each night, but not the same person two nights in a row.\r\n\r\n";
        $help_msg .= "Available Commands\r\n------------------------\r\n";
        $help_msg .= "|_  !start - Starts a new game with everyone in the channel participating\r\n";
        $help_msg .= "|_  !start @user1 @user2 @user3 - Starts a new game with the three specified users participating\r\n";
        $help_msg .= "|_  !vote @user1 - Vote for a player during the Day.\r\n";
        $help_msg .= "|_  !see #channel @user1 -  Seer only. As the seer, find out if user is villager or werewolf. #channel is the name of the channel you're playing in\r\n";
        $help_msg .= "|_  !kill #channel @user1 - Werewolf only. As a werewolf, in a PM to the bot, you can vote to kill a user each night. Must be unanimous amongst all werewolves.\r\n";
        $help_msg .= "|_  !guard #channel @user1 - Bodyguard only. The bodyguard can protect a player from being eliminated once each night. Cant select the same user two nights in a row.\r\n";
        $help_msg .= "|_  !end - Cause the game to end prematurely\r\n";

        $this->client->getDMByUserId($this->userId)->then(function(DirectMessageChannel $dm) use ($client, $help_msg) {
            $client->send($help_msg, $dm);
        });
    }
}