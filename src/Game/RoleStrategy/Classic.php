<?php namespace Slackwolf\Game\RoleStrategy;

use Slackwolf\Game\GameState;
use Slackwolf\Game\Role;
use Slackwolf\Game\OptionName;
use Slackwolf\Game\Roles\Villager;
use Slackwolf\Game\Roles\Tanner;
use Slackwolf\Game\Roles\Lycan;
use Slackwolf\Game\Roles\Beholder;
use Slackwolf\Game\Roles\Bodyguard;
use Slackwolf\Game\Roles\Hunter;
use Slackwolf\Game\Roles\Seer;
use Slackwolf\Game\Roles\Werewolf;
use Slackwolf\Game\Roles\Witch;
use Slackwolf\Game\Roles\WolfMan;
use Slackwolf\Game\Roles\Fool;
use Slackwolf\Game\Roles\Cursed;

/**
 * Defines the Classic class.
 *
 * @package Slackwolf\Game\RoleStrategy
 */
class Classic implements RoleStrategyInterface
{

    private $roleListMsg;
    private $minExtraRolesNumPlayers = 4;

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
        $num_evil = floor($num_players / 3);
        $num_good = $num_players - $num_evil;

        $num_seer = $optionsManager->getOptionValue(OptionName::ROLE_SEER) ? 1 : 0;
        $num_witch = $optionsManager->getOptionValue(OptionName::ROLE_WITCH) ? 1 : 0;
        $num_hunter = $optionsManager->getOptionValue(OptionName::ROLE_HUNTER) ? 1 : 0;

        $requiredRoles = [
            Role::SEER => $num_seer,
            Role::WEREWOLF => $num_evil
        ];

        // witch role on
        if ($optionsManager->getOptionValue(OptionName::ROLE_WITCH)){
            $requiredRoles[Role::WITCH] = 1;
        }

        // hunter role on
        if ($optionsManager->getOptionValue(OptionName::ROLE_HUNTER)){
            $requiredRoles[Role::HUNTER] = 1;
        }

        $optionalRoles = [
            Role::VILLAGER => max($num_good - $num_seer + $num_witch + $num_hunter, 0)
        ];

        $this->roleListMsg = "Required: [".($num_seer > 0 ? "Seer, " : "").
            ($num_witch > 0 ? "Witch, " : "").
            ($num_hunter > 0 ? "Hunter, " : "").
            "Werewolf, Villager]";

        $possibleOptionalRoles = [new Villager()];
        $optionalRoleListMsg = "";
        if ($num_players >= $this->minExtraRolesNumPlayers) {

            if (($num_seer > 0)
                && $optionsManager->getOptionValue(OptionName::ROLE_BEHOLDER)){
                $optionalRoles[Role::BEHOLDER] = 1;
                $possibleOptionalRoles[] = new Beholder();
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Beholder";
            }

            if ($optionsManager->getOptionValue(OptionName::ROLE_BODYGUARD)){
                $optionalRoles[Role::BODYGUARD] = 1;
                $possibleOptionalRoles[] = new Bodyguard();
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Bodyguard";
            }

            if ($optionsManager->getOptionValue(OptionName::ROLE_LYCAN)){
                $optionalRoles[Role::LYCAN] = 1;
                $possibleOptionalRoles[] = new Lycan();
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Lycan";
            }

            if ($optionsManager->getOptionValue(OptionName::ROLE_WOLFMAN)){
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Wolfman";
            }

            if ($optionsManager->getOptionValue(OptionName::ROLE_TANNER)){
                $optionalRoles[Role::TANNER] = 1;
                $possibleOptionalRoles[] = new Tanner();
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Tanner";
            }

            if ($optionsManager->getOptionValue(OptionName::ROLE_FOOL)){
                $optionalRoles[Role::FOOL] = 1;
                $possibleOptionalRoles[] = new Fool();
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Fool";
            }
            
            if ($optionsManager->getOptionValue(OptionName::ROLE_CURSED)){
                $optionalRoles[Role::CURSED] = 1;
                $possibleOptionalRoles[] = new Cursed();
                $optionalRoleListMsg .= (strlen($optionalRoleListMsg) > 0 ? ", " : "")."Cursed";
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
                    if($role == Role::WITCH)
                        $rolePool[] = new Witch();
                    if($role == Role::HUNTER)
                        $rolePool[] = new Hunter();
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
        if($optionsManager->getOptionValue(OptionName::ROLE_WOLFMAN) ? 1 : 0) {
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

    public function firstNight($gameManager, $game, $msg)
    {
        if ($gameManager->optionsManager->getOptionValue(OptionName::ROLE_SEER) || $gameManager->optionsManager->getOptionValue(OptionName::ROLE_FOOL)) {
            $msg .= " The game will begin when the Seer(s) (if there is one) chooses someone.";
            $gameManager->sendMessageToChannel($game, $msg);
        } else {
            $gameManager->sendMessageToChannel($game, $msg);
            $gameManager->changeGameState($game->getId(), GameState::DAY);
        }
    }
}
