<?php namespace Slackwolf;

/**
 * GameRegistry.php
 *
 * @company StitchLabs
 * @project slackwolf
 *
 * @author  Chris Gillis
 */
use Exception;

/**
 * Class GameRegistry
 *
 * @package Slackwolf
 */
class GameRegistry
{
    protected $game;

    public function exists() {
        return $this->game !== null;
    }

    public function set(Game $game)
    {
        if ($this->game !== null) {
            throw new Exception("Registry key is taken.");
        }

        $this->game = $game;
    }

    public function get() {
        if ($this->exists()) {
            return $this->game;
        }

        return false;
    }

    public function clear() {
        if ($this->exists()) {
            $this->game = null;
        }
    }
}