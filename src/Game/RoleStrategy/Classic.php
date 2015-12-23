<?php namespace Slackwolf\Game\RoleStrategy;

use Slackwolf\Game\Role;

class Classic implements RoleStrategyInterface
{

    public function assign(array $players)
    {
        $num_players = count($players); // 6
        $num_evil = floor($num_players / 3); // 2
        $num_good = $num_players - $num_evil; // 4

        $requiredRoles = [
            Role::SEER => 1,
            Role::WEREWOLF => $num_evil
        ];

        $optionalRoles = [
            Role::VILLAGER => max($num_good - 1, 0)
        ];

        if ($num_players >= 6) {
            $optionalRoles += [
                Role::TANNER => 1,
                Role::LYCAN => 1,
                Role::BEHOLDER => 1,
                Role::BODYGUARD => 1
            ];
        }

        shuffle($optionalRoles);


        $rolePool = [];

        foreach ($requiredRoles as $role => $num_role) {
            for ($i = 0; $i < $num_role; $i++) {
                if (count($rolePool) < $num_players) {
                    $rolePool[] = $role;
                }
            }
        }

        foreach ($optionalRoles as $role => $num_role) {
            for ($i = 0; $i < $num_role; $i++) {
                if (count($rolePool) < $num_players) {
                    $rolePool[] = $role;
                }
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
