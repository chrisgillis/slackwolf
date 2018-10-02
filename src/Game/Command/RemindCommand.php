<?php namespace Slackwolf\Game\Command;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slack\DirectMessageChannel;
use Slackwolf\Game\Game;
use Slackwolf\Game\GameState;
use Slackwolf\Game\OptionName;

/**
 * Defines the RemindCommand class.
 */
class RemindCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        $client = $this->client;

        if ($this->channel[0] == 'D') {
            $client->getDMByUserId($this->userId)
                ->then(function(DirectMessageChannel $dm) use ($client) {
                    $client->send(":warning: Run this command in the game channel.", $dm);
                });
            return;
        }

        if (!$this->gameManager->hasGame($this->channel) || $this->game->state == GameState::LOBBY) {
            $client->getChannelGroupOrDMByID($this->channel)
                ->then(function (ChannelInterface $channel) use ($client) {
                    $client->send(":warning: No game in progress.", $channel);
                });
            return;
        }

        // Look for current game and player
        $game = $this->gameManager->getGame($this->channel);
        $player = $game->getPlayerById($this->userId);

        if($player){
            $roleName = $player->role->getName();
            $roleDescription = $player->role->getDescription();

            // DM the player his current role and description
            $reminder_msg = "Your current role is:\r\n" . '_' . $roleName . '_ - ' . $roleDescription;

            $client->getDMByUserID($player->getId())
                ->then(function(DirectMessageChannel $dm) use ($client, $reminder_msg) {
                    $client->send($reminder_msg,$dm);
                });
        }

    }
}