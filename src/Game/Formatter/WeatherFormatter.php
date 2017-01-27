<?php namespace Slackwolf\Game\Formatter;

use Slackwolf\Game\Game;
use Slackwolf\Game\GameState;

/**
 * Defines the WeatherFormatter class.
 */
class WeatherFormatter
{


    /**
     * @param $weightedValues
     *
     * @return string
     *
     * Utility function for getting random values with weighting.
     * Pass in an associative array, such as array('A'=>5, 'B'=>45, 'C'=>50)
     * An array like this means that "A" has a 5% chance of being selected, "B" 45%, and "C" 50%.
    */

    public static function getWeather(array $weightedValues) {

        $rand = mt_rand(1, (int) array_sum($weightedValues));

        foreach ($weightedValues as $key => $value) {

            $rand -= $value;
            if ($rand <= 0) {

                return $key;

            }
        }
    }

    /**
     * @param $game
     *
     * @return string
     */

    public static function format(Game $game)
    {
        // Send message according to GameState and weather
        $state = $game->state;
        /** 
        * Create an associate array with the value as weight.
        * To modify the weight, just change the value with the integer.
        * Raining = 25%, cloudy = 50%, sunny= 25%  
        */
        $weight = array(
            "rainy" => 25, 
            "cloudy" => 50, 
            "sunny" => 25,
            );

        $Weather = WeatherFormatter::getWeather($weight);
        $messages = array(":rain_cloud:",":cloud:",":sunny:");

        if ($state == GameState::FIRST_NIGHT){

            if (file_exists("src/Game/Data/first_weather.txt")){
                $messages = file("src/Game/Data/first_weather.txt");
            }

            if ($Weather == "rainy"){
                $msg = $messages[0];
            }

            else if ($Weather == "cloudy"){
                $msg = $messages[1];
            }

            else{
                $msg = $messages[2];
            }

        }

        if ($state == GameState::DAY){
            if (file_exists("src/Game/Data/day_weather.txt")){
                $messages = file("src/Game/Data/day_weather.txt");
            }

            if ($Weather == "rainy"){
                $msg = $messages[0];
            }

            else if ($Weather == "cloudy"){
                $msg = $messages[1];
            }

            else{
                $msg = $messages[2];
            }

        }

        if ($state == GameState::NIGHT){
            if (file_exists("src/Game/Data/night_weather.txt")){
                $messages = file("src/Game/Data/night_weather.txt");
            }

            if ($Weather == "rainy"){
                $msg = $messages[0];
            }

            else if ($Weather == "cloudy"){
                $msg = $messages[1];
            }

            else{
                $msg = $messages[2];
            }

        }

    return $msg;
    }
}