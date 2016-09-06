<?php namespace Slackwolf\Game\RoleStrategy;

class RoleStrategyFactory
{
    public function createRoleStrategy($roleStrategyOption)
    {
        switch($roleStrategyOption)
        {
            case "vanilla":
                return new Vanilla();
            case "classic":
            default:
                return new Classic();
        }
    }
}