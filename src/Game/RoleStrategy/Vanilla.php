<?php namespace Slackwolf\Game\RoleStrategy;

use Slackwolf\Game\Roles\Seer;
use Slackwolf\Game\Roles\Villager;
use Slackwolf\Game\Roles\Werewolf;

class Vanilla implements RoleStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getRoleListMsg()
    {
        return "Required: [Seer, Werewolf, Villager]. No other roles.";
    }
    
    /**
     * {@inheritdoc}
     */
    public function assign(array $players, $gameManager)
    {
        $num_players = count($players);
        $num_evil = floor($num_players / 3);
        $num_good = $num_players - $num_evil;
        
        $rolePool = [];
        $rolePool[] = new Seer();
        for($i = 1; $i < $num_players; $i++)
        {
            if ($i < $num_good) $rolePool[] = new Villager();
            else $rolePool[] = new Werewolf();
        }
        shuffle($rolePool);
        $i = 0;
        foreach($players as $player)
        {
            $player->role = $rolePool[$i];
            $i++; 
        }

        return $players;
    }
}