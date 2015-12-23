<?php namespace Slackwolf\Game\RoleStrategy;

use Slackwolf\Game\Role;

class Classic implements RoleStrategyInterface
{

    public function assign(array $players)
    {
        $num_players = count($players); // 6
        $num_evil = floor($num_players / 3); // 2
        $num_good = $num_players - $num_evil; // 4

        $roles = [
            Role::SEER => 1
        ];

        if ($num_players >= 5) {
            $roles[Role::BODYGUARD] = 1;
        }

        if ($num_players >= 6) {
            $roles[Role::TANNER] = 1;
        }

        $roles += [
            Role::VILLAGER => max($num_good - count($roles), 0),
            Role::WEREWOLF => $num_evil
        ];

        // In games >= 6 players, lets randomly throw in some roles to switch things up
        if ($num_players >= 6) {
            $roles[Role::LYCAN] = 1;
            $roles[Role::BEHOLDER] = 1;
        }

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
