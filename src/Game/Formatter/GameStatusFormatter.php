<?php namespace Slackwolf\Game\Formatter;

use Slackwolf\Game\Game;
use Slackwolf\Game\GameState;
use Slackwolf\Game\Role;

class GameStatusFormatter
{
    public static function format(Game $game)
    {
        $msg = ":memo: Game Status\r\n--------------------------------------------------------------\r\n";

        switch($game->state) {

            case GameState::DAY:
                $msg .= ":sun_small_cloud:  It is now Day.  Please vote!\r\n";
                break;

            case GameState::FIRST_NIGHT:
            case GameState::NIGHT:
                $msg .= ":crescent_moon:  The night lingers on ... \r\n \r\n";

                $numSeer = $game->getNumRole(Role::SEER);
                $numBodyguard = $game->getNumRole(Role::BODYGUARD);
                $numWitch = $game->getNumRole(Role::WITCH);

                if ($numSeer > 0 && !$game->seerSeen) {
                    $msg .= " - Waiting on Seer\r\n";
                }

                if ($game->state == GameState::NIGHT) {
                    if (!$game->wolvesVoted) {
                        $msg .= " - Waiting on Wolves\r\n";
                    }

                    if ($numWitch > 0 && (!$game->witchPoisoned || !$game->witchHealed)) {
                        $msg .= " - Waiting on Witch\r\n";
                    }

                    if ($numBodyguard > 0 && !$game->getGuardedUserId()) {
                        $msg .= " - Waiting on Bodyguard\r\n";
                    }
                }
                break;

            default:
                $msg .= "No Game Running\n";
        }

        $msg .= "\r\n--------------------------------------------------------------\r\n";

        return $msg;
    }
}