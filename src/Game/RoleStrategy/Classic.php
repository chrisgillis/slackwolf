<?php namespace Slackwolf\Game\RoleStrategy;

use Slackwolf\Game\Role;

class Classic implements RoleStrategyInterface
{

    public function assign(array $players)
    {
        $num_players = count($players);
        $num_evil = floor($num_players / 3);
        $num_good = $num_players - $num_evil;

        $roles = [
            Role::SEER => 1,
            Role::VILLAGER => max($num_good - 1, 0),
            Role::WEREWOLF => $num_evil
        ];

        $rolePool = [];

        foreach ($roles as $role => $num_role) {
            for ($i = 0; $i < $num_role; $i++) {
                $rolePool[] = $role;
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
}