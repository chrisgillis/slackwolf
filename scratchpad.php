<?php

$players = [
new Player("Chris"),
new Player("Mike"),
new Player("Steven"),
new Player("Will")
];

$gameOptions = [
GameOption::VILLAGERS_MUST_LYNCH,
GameOption::WEREWOLVES_MUST_KILL
];

$game = new Game($players, $gameOptions);

$game->play();