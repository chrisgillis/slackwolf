<?php namespace Slackwolf\Game\Formatter;

class RoleSummaryFormatter
{
    /**
     * @param \Slack\User[] $players
     * @param \Slack\User[] $originalPlayers
     *
     * @return string
     */
    public static function format(array $players, array $originalPlayers)
    {
        $roleSummary = "";

        foreach ($originalPlayers as $og)
        {
            $roleSummary = "@{$og->getUsername()} ({$og->role}) - ";

            if (isset($players[$og->getId()])) {
                $roleSummary .= ":white_check_mark:\r\n";
            } else {
                $roleSummary .= ":x:\r\n";
            }
        }

        return $roleSummary;
    }
}