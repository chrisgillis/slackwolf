<?php namespace Slackwolf\Game\RoleStrategy;

use Slackwolf\Game\Role;
use Slackwolf\Game\Roles\Villager;
use Slackwolf\Game\Roles\Werewolf;

class Vanilla implements RoleStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getRoleListMsg()
    {
        return "Required: [Werewolf, Villager]. No other roles.";
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
        for($i = 0; i < $num_players; $i++)
        {
            if ($i < $num_good) $rolePool[$i] = new Villager();
            else $rolePool[$i] = new Werewolf();
        }
        shuffle($rolePool);
        for($i = 0; i < $num_players; $i++)
        {
            $players[$i]->role = $rolePool[$i]; 
        }
    }
}