<?php namespace Slackwolf\Game\Formatter;

class UserIdFormatter
{
    public static function format($userId)
    {
        return trim($userId, '<>@\t\n\r\x0B');
    }
}