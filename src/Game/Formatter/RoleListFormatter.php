<?php namespace Slackwolf\Game\Formatter;

class RoleListFormatter
{
    /**
     * @param \Slack\User[] $players
     *
     * @return string
     */
    public static function format(array $players)
    {
        $roleList = [];

        foreach ($players as $player)
        {
            $roleList[] = $player->role;
        }

        shuffle($roleList);

        return implode(', ', $roleList);
    }
}