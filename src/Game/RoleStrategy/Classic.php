<?php namespace Slackwolf\Game\RoleStrategy;

use Slackwolf\Game\Role;

class Classic implements RoleStrategyInterface
{

    private $roleListMsg;

    /**
     * @return string
     */
    public function getRoleListMsg()
    {
        return $this->roleListMsg;
    }


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

        $possibleOptionalRoles = [Role::VILLAGER];

        if ($num_players >= 6) {
            $optionalRoles[Role::TANNER] = 1;
            $optionalRoles[Role::LYCAN] = 1;
            $optionalRoles[Role::BEHOLDER] = 1;
            $optionalRoles[Role::BODYGUARD] = 1;
            $possibleOptionalRoles = [Role::VILLAGER, Role::TANNER, Role::LYCAN, Role::BEHOLDER,Role::BODYGUARD];
        }

        shuffle($possibleOptionalRoles);

        $this->roleListMsg = "Required: [Seer, Werewolf, Villager]";

        if ($num_players >= 6) {
            $this->roleListMsg .= "+ Optional: [Tanner,Lycan,Beholder,Bodyguard]";
        }

        $rolePool = [];

        foreach ($requiredRoles as $role => $num_role) {
            for ($i = 0; $i < $num_role; $i++) {
                if (count($rolePool) < $num_players) {
                    $rolePool[] = $role;
                }
            }
        }

        foreach ($possibleOptionalRoles as $possibleRole) {
            $num_role = $optionalRoles[$possibleRole];
            for ($i = 0; $i < $num_role; $i++) {
                if (count($rolePool) < $num_players) {
                    $rolePool[] = $possibleRole;
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
