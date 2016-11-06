<?php namespace Slackwolf\Game\Formatter;

use Slackwolf\Game\Game;
use Slackwolf\Game\GameState;

/**
 * Defines the WeatherFormatter class.
 */
class WeatherFormatter
{

    /**
     * @param $game
     *
     * @return string
     */
    public static function format(Game $game)
    {
        // Send message according to GameState and weather
        $state= $game->state;

        if($state== GameState::FIRST_NIGHT){
            if($game->weather==1){
                $msg= ":moon: :rain_cloud: The rain comes down in torrents as the village sleeps, unaware of the horror the lurks outside in the wet. It is the middle of the night.";
            }
            else if($game->weather==1){
                $msg= ":moon: :cloud: The cloud covered the sky blocking even the few glimmering light pass. ";
            }
            else{
                $msg= "The village sleeps, unaware of the horror the lurks outside in the dark. It is the middle of the night.";
            }

        }
        if($state== GameState::DAY){
             if($game->weather==1){
                $msg= ":sunrise: The sun rises and the villagers awake. It is still raining, but it slows somewhat, allowing momentary respite from the cold, wet hell that we all live in.";
            }
            else if($game->weather==1){
                $msg= ":cloud: There is no sky today, only a thick layer of cloud blocking the sky. The air was cooler, announcing rain in the day to come.";
            }
            else{
                $msg= ":sunny: The morning came and the sun, high in the sky, gave hope that one day this madness will end.";
            }
        }
        if($state== GameState::NIGHT){
             if($game->weather==1){
                $msg= ":moon: :zzz: The sun sets, and the hard rain makes it difficult to hear anything outside. Villagers bar their doors, take long pulls of :beer:, and try not to think of what might lurk beyond the feeble candlelight.";
            }
            else if($game->weather==1){
                $msg= ":moon: :fog: A thick fog covered the village blocking all of the light. The Villagers bar their door waiting without rest until sunrise.";
            }
            else{
                $msg= ":full_moon: The sun set, and the moon lights up the sky giving a glimmer of hope. The Villagers bar their doors waiting in fear until sunrise.";
            }
        }

        return $msg;
    }
}