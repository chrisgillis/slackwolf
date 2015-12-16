<?php

/* Register the Composer autoloader */
require __DIR__ . '/vendor/autoload.php';

/* Load the dotenv package */
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

/* Initialise php stuff */
date_default_timezone_set(getenv('TIMEZONE'));

$token = getenv('BOT_TOKEN');

$loop = React\EventLoop\Factory::create();

$client = new Slack\RealTimeClient($loop);
$client->setToken($token);

$client->on('message', function ($data) use ($client) {
    echo "Someone typed a message: " . $data['text'] . "\n";
    $client->disconnect();
});

$client->connect()->then(function() {
    echo "Connected\n";
});

$loop->run();
