<?php namespace Slackwolf\Game\RoleStrategy;


use Slackwolf\Game\GameState;
use Slackwolf\Game\Roles\Villager;
use Slackwolf\Game\Roles\Werewolf;


/**
 * Defines the Vanilla class.
 *
 * @package Slackwolf\Game\RoleStrategy
 */
class Vanilla implements RoleStrategyInterface
{

    private $roleListMsg;

    /**
     * {@inheritdoc}
     */
    public function getRoleListMsg()
    {
        return $this->roleListMsg;
    }


    /**
     * {@inheritdoc}
     */
    public function assign(array $players, $optionsManager)
    {
        $num_players = count($players);
        $num_werewolf = floor($num_players / 3);
        $num_villager = $num_players - $num_werewolf;

        $this->roleListMsg = "Required: [Werewolf, Villager]";

        $rolePool = [];

        for ($i = 0; $i < $num_werewolf; $i++) {
            if (count($rolePool) < $num_players) {
                $rolePool[] = new Werewolf();
            }
        }

        for ($i = 0; $i < $num_villager; $i++) {
            if (count($rolePool) < $num_players) {
                $rolePool[] = new Villager();
            }
        }

        shuffle($rolePool);

        $i = 0;
        foreach ($players as $player) {
            $player->role = $rolePool[$i];
            $i++;
        }

        return $players;
    }

    public function firstNight($gameManager, $game, $msg) {
        $gameManager->sendMessageToChannel($game, $msg);
        $gameManager->changeGameState($game->getId(), GameState::DAY);
    }
}
