<?php namespace Slackwolf\Game\Formatter;

/**
 * Defines the UserIdFormatter class.
 */
class UserIdFormatter
{

    /**
     * @param $userId
     * @param $users
     *
     * @return string
     */
    public static function format($userId, $users)
    {
        $rtn = trim($userId, "<>@\t\n\r\x0B"); //have to use double quotes for escape to work properly
        
        if (! isset($users[$rtn])) {
            //Not a valid userId, check it against usernames (not case sensitive!)
            foreach ($users as $key => $user) {
                if (strcasecmp($user->getUsername(), $rtn) == 0) {
                    $rtn = $user->getId();
                    break;
                }
            }
        }
                
        return $rtn;
    }
}