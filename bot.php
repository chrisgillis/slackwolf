<?php

/**
 * Slackwolf - A werewolf moderator for Slack
 *
 * @package Slackwolf
 * @author Chris Gillis
 */

/*
 * Register the Composer autoloader
 */
use Dotenv\Dotenv;
use Slackwolf\Slackwolf;

require __DIR__.'/vendor/autoload.php';

/*
* Load dotenv to be able to access .env configuration variables
*/
$dotenv = new Dotenv(__DIR__);
$dotenv->load();

/*
 * Start the bot
 */
(new Slackwolf())->run();