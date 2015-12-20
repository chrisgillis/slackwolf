<?php namespace Slackwolf\Game\Formatter;

class PlayerListFormatter
{
    /**
     * @param \Slack\User[] $players
     *
     * @return string
     */
    public static function format(array $players, $withRoles = false)
    {
        $playerList = [];

        foreach ($players as $player)
        {
            $str = '@'.$player->getUsername();

            if ($withRoles) {
                $str .= ' (' . $player->role . ')';
            }

            $playerList[] = $str;
        }

        return implode(', ', $playerList);
    }
}