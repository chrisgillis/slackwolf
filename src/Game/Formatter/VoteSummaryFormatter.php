<?php namespace Slackwolf\Game\Formatter;

use Slackwolf\Game\Game;

/**
 * Defines the VoteSummaryFormatter class.
 */
class VoteSummaryFormatter
{

    /**
     * @param Game $game
     *
     * @return string
     */
    public static function format(Game $game)
    {
        $msg = ":memo: Town Ballot\r\n
        - - - - - - - - - - - - - - - - - - - - - - - -\r\n";

        foreach ($game->getVotes() as $voteForId => $voters)
        {
            $voteForPlayer = $game->getPlayerById($voteForId);
            $numVoters = count($voters);

            if ($voteForId == 'noone'){
                $msg .= ":peace_symbol: No lynch\t\t | ({$numVoters}) | ";
            } else {
                $msg .= ":coffin: Lynch @{$voteForPlayer->getUsername()}\t\t | ({$numVoters}) | ";
            }

            $voterNames = [];

            foreach ($voters as $voter)
            {
                $voter = $game->getPlayerById($voter);
                $voterNames[] = '@'.$voter->getUsername();
            }

            $msg .= implode(', ', $voterNames) . "\r\n";
        }

        $msg .= "\r\n- - - - - - - - - - - - - - - - - - - - - - - -\r\n:hourglass: Remaining Voters: ";

        $playerNames = [];

        foreach ($game->getLivingPlayers() as $player)
        {
            if ( ! $game->hasPlayerVoted($player->getId())) {
                $playerNames[] = '@'.$player->getUsername();
            }
        }

        if (count($playerNames) > 0) {
            $msg .= implode(', ', $playerNames);
        } else {
            $msg .= "None";
        }

        $msg .= "\r\n- - - - - - - - - - - - - - - - - - - - - - - -\r\n";

        return $msg;
    }
}