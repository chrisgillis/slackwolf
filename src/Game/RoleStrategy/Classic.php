<?php namespace Slackwolf\Game\RoleStrategy;

use Slackwolf\Game\Role;
use Slackwolf\Game\OptionsManager;
use Slackwolf\Game\OptionName;

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


    public function assign(array $players, $optionsManager)
    {
        $num_players = count($players); // 6
        $num_evil = floor($num_players / 3); // 2
        $num_good = $num_players - $num_evil; // 4
        $num_seer = $optionsManager->getOptionValue(OptionName::role_seer) ? 1 : 0;
        $requiredRoles = [
            Role::SEER => $num_seer,
            Role::WEREWOLF => $num_evil
        ];

        $optionalRoles = [
            Role::VILLAGER => max($num_good - $num_seer, 0)
        ];
        
        $this->roleListMsg = "Required: [".($num_seer > 0 ? "Seer, " : "")."Werewolf, Villager]";

        $possibleOptionalRoles = [Role::VILLAGER];
        $optionalRoleListMsg = "";
        if ($num_players >= 6) {
            $possibleOptionalRoles = [];
            if ($optionsManager->getOptionValue(OptionName::role_tanner)){
                $optionalRoles[Role::TANNER] = 1;
                $possibleOptionalRoles[] = Role::TANNER;
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Tanner";
            }
            if ($optionsManager->getOptionValue(OptionName::role_lycan)){
                $optionalRoles[Role::LYCAN] = 1;
                $possibleOptionalRoles[] = Role::LYCAN;
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Lycan";
            }
            if ($num_seer > 0 
                && $optionsManager->getOptionValue(OptionName::role_beholder)){
                $optionalRoles[Role::BEHOLDER] = 1;
                $possibleOptionalRoles[] = Role::BEHOLDER;
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Beholder";
            }
            if ($optionsManager->getOptionValue(OptionName::role_bodyguard)){
                $optionalRoles[Role::BODYGUARD] = 1;
                $possibleOptionalRoles[] = Role::BODYGUARD;
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Bodyguard";
            }
        }

        shuffle($possibleOptionalRoles);


        if ($num_players >= 6 && strlen($optionalRoleListMsg) > 0) {
            $this->roleListMsg .= "+ Optional: [".$optionalRoleListMsg."]";
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
