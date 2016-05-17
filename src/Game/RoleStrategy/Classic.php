<?php namespace Slackwolf\Game\RoleStrategy;

use Slackwolf\Game\Role;
use Slackwolf\Game\OptionsManager;
use Slackwolf\Game\OptionName;
use Slackwolf\Game\Roles\Villager;
use Slackwolf\Game\Roles\Tanner;
use Slackwolf\Game\Roles\Lycan;
use Slackwolf\Game\Roles\Beholder;
use Slackwolf\Game\Roles\Bodyguard;
use Slackwolf\Game\Roles\Seer;
use Slackwolf\Game\Roles\Werewolf;
use Slackwolf\Game\Roles\WolfMan;

class Classic implements RoleStrategyInterface
{

    private $roleListMsg;
    private $minExtraRolesNumPlayers = 5;

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
        $num_witch = $optionsManager->getOptionValue(OptionName::role_witch) ? 1 : 0;

        $requiredRoles = [
            Role::SEER => $num_seer,
            Role::WEREWOLF => $num_evil
        ];

        // witch role on
        if ($optionsManager->getOptionValue(OptionName::role_witch)){
            $requiredRoles[Role::WITCH] = 1;
        }

        $optionalRoles = [
            Role::VILLAGER => max($num_good - $num_seer + $num_witch, 0)
        ];

        $this->roleListMsg = "Required: [".($num_seer > 0 ? "Seer, " : "").
            ($num_witch > 0 ? "Witch, " : "").
            "Werewolf, Villager]";

        $possibleOptionalRoles = [new Villager()];
        $optionalRoleListMsg = "";
        if ($num_players >= $this->minExtraRolesNumPlayers) {
            if ($optionsManager->getOptionValue(OptionName::role_tanner)){
                $optionalRoles[Role::TANNER] = 1;
                $possibleOptionalRoles[] = new Tanner();
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Tanner";
            }
            if ($optionsManager->getOptionValue(OptionName::role_lycan)){
                $optionalRoles[Role::LYCAN] = 1;
                $possibleOptionalRoles[] = new Lycan();
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Lycan";
            }
            if ($num_seer > 0
                && $optionsManager->getOptionValue(OptionName::role_beholder)){
                $optionalRoles[Role::BEHOLDER] = 1;
                $possibleOptionalRoles[] = new Beholder();
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Beholder";
            }
            if ($optionsManager->getOptionValue(OptionName::role_bodyguard)){
                $optionalRoles[Role::BODYGUARD] = 1;
                $possibleOptionalRoles[] = new Bodyguard();
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Bodyguard";
            }
        }

        shuffle($possibleOptionalRoles);

        if ($num_players >= $this->minExtraRolesNumPlayers && strlen($optionalRoleListMsg) > 0) {
            $this->roleListMsg .= "+ Optional: [".$optionalRoleListMsg."]";
        }

        $rolePool = [];

        foreach ($requiredRoles as $role => $num_role) {
            for ($i = 0; $i < $num_role; $i++) {
                if (count($rolePool) < $num_players) {
                    if($role == Role::SEER)
                        $rolePool[] = new Seer();
                    if($role == Role::WEREWOLF)
                        $rolePool[] = new Werewolf();
                }
            }
        }

        foreach ($possibleOptionalRoles as $possibleRole) {
            $num_role = $optionalRoles[$possibleRole->getName()];
            for ($i = 0; $i < $num_role; $i++) {
                if (count($rolePool) < $num_players) {
                    $rolePool[] = $possibleRole;
                }
            }
        }

        //If playing with Wolf Man, swap out a Werewolf for a Wolf Man.
        //Determine if Wolf man should be swapped randomly based off of # of players % 3
        //For now: (0 = 20%, 1 = 40%, 2 = 60%)
        if($optionsManager->getOptionValue(OptionName::role_wolfman) ? 1 : 0) {
            $threshold = (.2 + (($num_players % 3) * .2)) * 100;
            $randVal = rand(0, 100);
            if($randVal < $threshold) {
                foreach($rolePool as $key=>$role) {
                    if($role->isWerewolfTeam()) {
                        $rolePool[$key] = new WolfMan();
                        break;
                    }
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
