<?php namespace Slackwolf\Game\RoleStrategy;

use Exception;

class RoleStrategyFactory
{
    public function __construct()
    {
        // ... //
    }

    /**
     * @param string $type Which game mode to get a strategy for
     * @return mixed A class that exists in Slackwolf\Game\RoleStrategy\
     * @throws Exception
     */
    public static function build($type = '')
    {
        if ($type == '') {
            throw new Exception('Invalid Role Strategy.');
        } else {

            $className = "Slackwolf\Game\RoleStrategy\\".ucfirst($type);

            // Assuming Class files are already loaded using autoload concept
            if (class_exists($className)) {
                return new $className();
            } else {
                throw new Exception(' Role Strategy type not found.');
            }
        }
    }
}