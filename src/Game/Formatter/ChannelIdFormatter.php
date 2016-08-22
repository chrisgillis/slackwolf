<?php namespace Slackwolf\Game\Formatter;

/**
 * Defines the ChannelIdFormatter class.
 */
class ChannelIdFormatter
{

    /**
     * @param $userId
     *
     * @return string
     */
    public static function format($userId)
    {
        $trimmed = trim($userId, "<>#\t\n\r\x0B");

        if (strpos($trimmed, '|') !== false) {
            $trimmed = substr($trimmed, 0, strpos($trimmed,'|'));
        }

        return $trimmed;
    }
}