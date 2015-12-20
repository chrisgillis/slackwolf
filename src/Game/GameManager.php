<?php namespace Slackwolf\Game;

use Exception;
use Slack\Channel;
use Slack\DirectMessageChannel;
use Slack\RealTimeClient;
use Slackwolf\Game\Command\Command;
use Slackwolf\Game\Formatter\PlayerListFormatter;
use Slackwolf\Game\Formatter\RoleListFormatter;
use Slackwolf\Game\Formatter\RoleSummaryFormatter;
use Slackwolf\Game\Formatter\VoteSummaryFormatter;
use Slackwolf\Message\Message;

class GameManager
{
    private $games = [];

    private $commandBindings;
    private $client;

    public function __construct(RealTimeClient $client, array $commandBindings)
    {
        $this->commandBindings = $commandBindings;
        $this->client = $client;
    }

    public function input(Message $message)
    {
        $input = $message->getText();

        if ( ! is_string($input)) {
            return false;
        }

        if ( ! isset($input[0])) {
            return false;
        }

        if ($input[0] !== '!') {
            return false;
        }

        $input_array = explode(' ', $input);

        $command = $input_array[0];

        if (strlen($command) < 2) {
            return false;
        }

        $command = substr($command, 1);

        $args = [];

        foreach ($input_array as $i => $arg)
        {
            if ($i == 0) { continue; } // Skip the command

            if (empty($arg)) { continue; }

            $args[] = $arg;
        }

        if ($command == null) {
            return false;
        }

        $command = strtolower($command);

        if ( ! isset($this->commandBindings[$command])) {
            return false;
        }

        try
        {
            /** @var Command $command */
            $command = new $this->commandBindings[$command]($this->client, $this, $message, $args);
            $command->fire();
        } catch (Exception $e)
        {
            return false;
        }

        return true;
    }

    public function changeGameState($gameId, $newGameState)
    {
        $game = $this->getGame($gameId);

        if ( ! $game) {
            throw new Exception();
        }

        if ($game->isOver()) {
            $this->onGameOver($game);
            return;
        }

        if ($game->getState() == GameState::NIGHT && $newGameState == GameState::DAY) {
            $numSeer = $game->getNumRole(Role::SEER);

            if ($numSeer && ! $game->seerSeen()) {
                return;
            }

            $numWolf = $game->getNumRole(Role::WEREWOLF);

            if ($numWolf && ! $game->getWolvesVoted()) {
                return;
            }

            $this->onNightEnd($game);

            if ($game->isOver()) {
                $this->onGameOver($game);
                return;
            }
        }

        $game->changeState($newGameState);

        if ($newGameState == GameState::FIRST_NIGHT) {
            $this->onFirstNight($game);
        }

        if ($newGameState == GameState::DAY) {
            $this->onDay($game);
        }

        if ($newGameState == GameState::NIGHT) {
            $this->onNight($game);
        }
    }

    public function hasGame($id)
    {
        return isset($this->games[$id]);
    }

    /**
     * @param $id
     *
     * @return Game|bool
     */
    public function getGame($id)
    {
        if ($this->hasGame($id)) {
            return $this->games[$id];
        }

        return false;
    }

    public function newGame($id, array $users, $roleStrategy)
    {
        $game = new Game($id, $users, $roleStrategy);

        $this->addGame($game);

        $this->changeGameState($game->getId(), GameState::FIRST_NIGHT);
    }

    public function endGame($id, $enderUserId = null)
    {
        $game = $this->getGame($id);

        if ( ! $game) {
            return;
        }

        $playerList = RoleSummaryFormatter::format($game->getPlayers(), $game->getOriginalPlayers());

        $client = $this->client;

        $winningTeam = $game->whoWon();

        if($winningTeam !== null) {
            $winMsg = ":clipboard: Role Summary\r\n--------------------------------------------------------------\r\n{$playerList}\r\n\r\n:tada: The game is over. The ";
            if ($winningTeam == Role::VILLAGER) {
                $winMsg .= "Townsfolk ";
            }
            elseif ($winningTeam == Role::WEREWOLF) {
                $winMsg .= "Werewolves ";
            }
            else {
                $winMsg .= "UnknownTeam ";
            }
            $winMsg .= "are victorious!";
            $client->getChannelGroupOrDMByID($id)
                ->then(function (Channel $channel) use ($client, $playerList, $winMsg) {
                    $client->send($winMsg, $channel);
                });
        }

        unset($this->games[$id]);

        if ($enderUserId !== null) {
            $client->getChannelGroupOrDMByID($id)
                   ->then(function (Channel $channel) use ($client, $playerList, $enderUserId) {
                       $client->getUserById($enderUserId)
                              ->then(function (\Slack\User $user) use ($client, $playerList, $channel) {
                                  $client->send(":triangular_flag_on_post: The game was ended by @{$user->getUsername()}.\r\n\r\nRole Summary:\r\n----------------\r\n{$playerList}", $channel);
                              });
                   });
        }
    }

    public function vote(Game $game, $voterId, $voteForId)
    {
        if ( ! $game->hasPlayer($voterId)) {
            return;
        }

        if ( ! $game->hasPlayer($voteForId)) {
            return;
        }

        if ($game->hasPlayerVoted($voterId)) {
            return;
        }

        $game->vote($voterId, $voteForId);

        $voteMsg = VoteSummaryFormatter::format($game);

        $client = $this->client;

        $client->getChannelGroupOrDMByID($game->getId())
            ->then(function (Channel $channel) use ($client,$voteMsg) {
                $client->send($voteMsg, $channel);
            });

        if ( ! $game->votingFinished()) {
            return;
        }

        $votes = $game->getVotes();

        $vote_count = [];
        foreach ($votes as $lynch_player_id => $voters) {
            if ( ! isset($vote_count[$lynch_player_id])) {
                $vote_count[$lynch_player_id] = 0;
            }

            $vote_count[$lynch_player_id] += count($voters);
        }

        $players_to_be_lynched = [];

        $max = 0;
        foreach ($vote_count as $lynch_player_id => $num_votes) {
            if ($num_votes > $max) {
                $max = $num_votes;
            }
        }
        foreach ($vote_count as $lynch_player_id => $num_votes) {
            if ($num_votes == $max) {
                $players_to_be_lynched[] = $lynch_player_id;
            }
        }

        $lynchMsg = "\r\n:newspaper: With pitchforks in hand, the townsfolk killed: ";

        $lynchedNames = [];
        foreach ($players_to_be_lynched as $player_id) {
            $player = $game->getPlayerById($player_id);
            $lynchedNames[] = "@{$player->getUsername()} ({$player->role})";
            $game->removePlayer($player_id);
        }

        $lynchMsg .= implode(', ', $lynchedNames). "\r\n";

        $client->getChannelGroupOrDMByID($game->getId())
               ->then(function (Channel $channel) use ($client,$lynchMsg) {
                   $client->send($lynchMsg, $channel);
               });

        $this->changeGameState($game->getId(), GameState::NIGHT);
    }


    private function addGame(Game $game)
    {
        $this->games[$game->getId()] = $game;
    }

    private function onFirstNight(Game $game)
    {
        $client = $this->client;

        foreach ($game->getPlayers() as $player) {
            $client->getDMByUserId($player->getId())
                ->then(function (DirectMessageChannel $dmc) use ($client,$player,$game) {
                    $client->send("Your role is {$player->role}", $dmc);

                    if ($player->role == Role::WEREWOLF) {
                        if ($game->getNumRole(Role::WEREWOLF) > 1) {
                            $werewolves = PlayerListFormatter::format($game->getPlayersOfRole(Role::WEREWOLF));
                            $client->send("The other werewolves are: {$werewolves}", $dmc);
                        } else {
                            $client->send("You are the only werewolf.", $dmc);
                        }
                    }

                    if ($player->role == Role::SEER) {
                        $client->send("Seer, select a player by saying !see #channel @username.", $dmc);
                    }
                });
        }

        $playerList = PlayerListFormatter::format($game->getPlayers());
        $roleList = RoleListFormatter::format($game->getPlayers());

        $msg = ":wolf: A new game of Werewolf is starting! For a tutorial, type !help.\r\n\r\n";
        $msg .= "Players: {$playerList}\r\n";
        $msg .= "Roles: {$roleList}\r\n\r\n";

        $msg .= ":crescent_moon: :zzz: It is the middle of the night and the village is sleeping. The game will begin when the Seer chooses someone.";

        $this->client->getChannelGroupOrDMByID($game->getId())
            ->then(function (Channel $channel) use ($msg, $client) {
                $client->send($msg, $channel);
            });
    }

    private function onDay(Game $game)
    {
        $client = $this->client;

        $remainingPlayers = PlayerListFormatter::format($game->getPlayers());

        $dayBreakMsg = ":sunrise: The sun rises from the horizon and the villagers awake.\r\n";
        $dayBreakMsg .= "Remaining Players: {$remainingPlayers}\r\n\r\n";
        $dayBreakMsg .= "Villagers, find the Werewolves! Type !vote @username to vote to lynch a player.";

        $this->client->getChannelGroupOrDMByID($game->getId())
            ->then(function (Channel $channel) use ($client, $dayBreakMsg) {
                $client->send($dayBreakMsg, $channel);
            });
    }

    private function onNight(Game $game)
    {
        $client = $this->client;
        $nightMsg = ":crescent_moon: :zzz: The sun sets and the villagers go to sleep.";

        $this->client->getChannelGroupOrDMByID($game->getId())
             ->then(function (Channel $channel) use ($client, $nightMsg) {
                 $client->send($nightMsg, $channel);
             });

        $wolves = $game->getPlayersOfRole(Role::WEREWOLF);

        $wolfMsg = ":crescent_moon: It is night and it is time to hunt. Type !kill #channel @player to make your choice. ";

        foreach ($wolves as $wolf)
        {
             $this->client->getDMByUserId($wolf->getId())
                  ->then(function (DirectMessageChannel $channel) use ($client,$wolfMsg) {
                      $client->send($wolfMsg, $channel);
                  });
        }

        $seerMsg = ":mag_right: Seer, select a player by saying !see #channel @username.";

        $seers = $game->getPlayersOfRole(Role::SEER);

        foreach ($seers as $seer)
        {
            $this->client->getDMByUserId($seer->getId())
                 ->then(function (DirectMessageChannel $channel) use ($client,$seerMsg) {
                     $client->send($seerMsg, $channel);
                 });
        }
    }

    private function onNightEnd(Game $game)
    {
        $client = $this->client;

        $votes = $game->getVotes();

        foreach ($votes as $lynch_id => $voters) {
            $player = $game->getPlayerById($lynch_id);
            $game->removePlayer($lynch_id);

            $killMsg = ":skull_and_crossbones: @{$player->getUsername()} was killed during the night.";

            $client->getChannelGroupOrDMByID($game->getId())
                ->then(function(Channel $channel) use ($client,$killMsg) {
                    $client->send($killMsg, $channel);
                });
        }
    }

    private function onGameOver(Game $game)
    {
        $game->changeState(GameState::OVER);
        $this->endGame($game->getId());
    }
}