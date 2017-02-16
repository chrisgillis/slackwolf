<?php namespace Slackwolf\Game;

use Exception;
use Slack\Channel;
use Slack\ChannelInterface;
use Slack\DirectMessageChannel;
use Slack\RealTimeClient;
use Slackwolf\Game\Command\Command;
use Slackwolf\Game\Formatter\PlayerListFormatter;
use Slackwolf\Game\Formatter\RoleListFormatter;
use Slackwolf\Game\Formatter\RoleSummaryFormatter;
use Slackwolf\Game\Formatter\VoteSummaryFormatter;
use Slackwolf\Game\Formatter\WeatherFormatter;
use Slackwolf\Message\Message;
use Slackwolf\Game\OptionsManager;
use Slackwolf\Game\OptionName;

/**
 * Defines the GameManager class.
 *
 * @package Slackwolf\Game
 */
class GameManager
{

    /**
     * An array of all current games.
     *
     * @var array
     */
    private $games;

    /**
     * @var array
     */
    private $commandBindings;

    /**
     * @var RealTimeClient
     */
    private $client;

    /**
     * @var \Slackwolf\Game\OptionsManager
     */
    public $optionsManager;

    /**
     * Defines the GameManager constructor.
     *
     * @param RealTimeClient $client
     * @param array $commandBindings
     */
    public function __construct(RealTimeClient $client, array $commandBindings)
    {
        $this->commandBindings = $commandBindings;
        $this->client = $client;
        $this->optionsManager = new OptionsManager();

        $this->games = [];
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    public function input(Message $message)
    {
        $input = $message->getText();

        if (!is_string($input) || !isset($input[0]) || $input[0] !== '!') {
            return FALSE;
        }

        // Example: [!kill, #channel, @name]
        $input_array = explode(' ', $input);

        // Remove "!" from first element of array and set to lowercase.
        $command = strtolower(substr($input_array[0], 1));

        if (strlen($command) < 2) {
            return false;
        }

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

    /**
     * Sends a message to a game.
     *
     * @param Game $game
     *   The game to send the message.
     *
     * @param $msg
     *   The message.
     */
    public function sendMessageToChannel($game, $msg)
    {
        $this->client->getChannelGroupOrDMByID($game->getId())
            ->then(function (ChannelInterface $channel) use ($msg) {
                $this->client->send($msg, $channel);
            });
    }


    /**
     * @param $gameId
     * @param $newGameState
     *
     * @throws Exception
     */
    public function changeGameState($gameId, $newGameState)
    {
        $game = $this->getGame($gameId);

        if ( ! $game) {
            throw new Exception();
        }

        if ($game->hunterNeedsToShoot) {
            $this->sendMessageToChannel($game, "Hunter still needs to kill someone.");
            return;
        }

        if ($game->hunterNeedsToShoot) {
            $this->sendMessageToChannel($game, "It is still night, and the Hunter still needs to kill someone.");
            return;
        }

        if ($game->isOver()) {
            $this->onGameOver($game);
            return;
        }

        // changing from night to day
        if ($game->getState() == GameState::NIGHT && $newGameState == GameState::DAY && !$game->nightEnded) {

            $numSeer = $game->getNumRole(Role::SEER);
            if ($numSeer && ! $game->seerSeen()) {
                return;
            }

            $numWolf = count($game->getWerewolves());

            if ($numWolf && ! $game->getWolvesVoted()) {
                return;
            }

            $numBodyguard = $game->getNumRole(Role::BODYGUARD);

            if ($numBodyguard && ! $game->getGuardedUserId()) {
                return;
            }

            $numWitch = $game->getNumRole(Role::WITCH);
            if ($numWitch && !$game->getWitchHealed()) {
                return;
            }

            if ($numWitch && !$game->getWitchPoisoned()) {
                return;
            }

            $this->onNightEnd($game);

            if ($game->hunterNeedsToShoot) {
                return;
            }

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

    /**
     * @param $id
     * @return bool
     */
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

    /**
     * @param $id
     * @param array $users
     * @param $roleStrategy
     */
    public function newGame($id, array $users, $roleStrategy)
    {
        $this->addGame(new Game($id, $users, $roleStrategy));
   }

    /**
     * @param $id
     */
    public function startGame($id)
    {
        $game = $this->getGame($id);
        if (!$this->hasGame($id)) { return; }
        $users = $game->getLobbyPlayers();
        if(count($users) < 3) {
            $this->sendMessageToChannel($game, "Cannot start a game with less than 3 players.");
            return;
        }

        $game->setWitchHealedUserId(null);
        $game->setWitchPoisonedUserId(null);

        $game->assignRoles();
        $this->changeGameState($id, GameState::FIRST_NIGHT);
    }

    /**
     * @param $id
     * @param null $enderUserId
     */
    public function endGame($id, $enderUserId = null)
    {
        $game = $this->getGame($id);

        if ( ! $game) {
            return;
        }

        $playerList = RoleSummaryFormatter::format($game->getLivingPlayers(), $game->getOriginalPlayers());

        $client = $this->client;
        $winningTeam = $game->whoWon();

        if($winningTeam !== null) {
            $winMsg = ":clipboard: Role Summary\r\n--------------------------------------------------------------\r\n{$playerList}\r\n\r\n:tada: The game is over. The ";
            if ($winningTeam == Role::VILLAGER) {
                $winMsg .= "Townsfolk are victorious!";
            }
            elseif ($winningTeam == Role::WEREWOLF) {
                $winMsg .= "Werewolves are victorious!";
            }
            elseif ($winningTeam == Role::TANNER) {
                $winMsg .= "Tanner is victorious!";
            }
            else {
                $winMsg .= "UnknownTeam is victorious!";
            }
            $this->sendMessageToChannel($game, $winMsg);
        }

        if ($enderUserId !== null) {
            $client->getUserById($enderUserId)
                   ->then(function (\Slack\User $user) use ($game, $playerList) {
                       $gameMsg = ":triangular_flag_on_post: The ";
                       $roleSummary = "";
                       if($game->getState() != GameState::LOBBY) {
                           $gameMsg .= "game was ended";
                           $roleSummary .= "\r\n\r\nRole Summary:\r\n----------------\r\n{$playerList}";
                       } else {
                           $gameMsg .= "lobby was closed";
                       }
                       $this->sendMessageToChannel($game, $gameMsg." by @{$user->getUsername()}.".$roleSummary);
                   });
        }

        unset($this->games[$id]);
    }

    /**
     * @param Game $game
     * @param $voterId
     * @param $voteForId
     *
     * @throws Exception
     */
    public function vote(Game $game, $voterId, $voteForId)
    {
        if ( ! $game->isPlayerAlive($voterId)) {
            return;
        }

        if ( ! $game->isPlayerAlive($voteForId)
                && ($voteForId != 'noone' || !$this->optionsManager->getOptionValue(OptionName::no_lynch))
                && $voteForId != 'clear') {
            return;
        }

        if ($game->dayEnded) {
            return;
        }

        if ($game->hasPlayerVoted($voterId)) {
            //If changeVote is not enabled and player has already voted, do not allow another vote
            if (!$this->optionsManager->getOptionValue(OptionName::changevote))
            {
                throw new Exception("Vote change not allowed.");
            }
            $game->clearPlayerVote($voterId);
        }

        if ($voteForId != 'clear') { //if voting for 'clear' just clear vote
            $game->vote($voterId, $voteForId);
        }
        $voteMsg = VoteSummaryFormatter::format($game);

        $this->sendMessageToChannel($game, $voteMsg);

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
            if ($num_votes == $max && $lynch_player_id != 'noone') {
                $players_to_be_lynched[] = $lynch_player_id;
            }
        }

        $lynchMsg = "\r\n";
        $hunterMsg = "\r\n";

        if (count($players_to_be_lynched) == 0) {
            $lynchMsg .= ":peace_symbol: The townsfolk decided not to lynch anybody today.";
        } elseif (count($players_to_be_lynched) > 1) {
            $lynchMsg .= ":peace_symbol: The townsfolk couldn't agree on who to lynch, so nobody is hung today.";
         }
         else {
            $lynchMsg .= ":newspaper: With pitchforks in hand, the townsfolk killed: ";

            $lynchedNames = [];
            foreach ($players_to_be_lynched as $player_id) {
                $player = $game->getPlayerById($player_id);
                $lynchedNames[] = "@{$player->getUsername()} ({$player->role->getName()})";
                $game->killPlayer($player_id);

                if ($player->role->isRole(Role::HUNTER)) {
                    $game->setHunterNeedsToShoot(true);
                    $hunterMsg .= ":bow_and_arrow: " . $player->getUsername() .
                        " as hunter you may shoot one person.  Type !shoot @playername, or !shoot noone.";
                }
            }

            $lynchMsg .= implode(', ', $lynchedNames). "\r\n";
        }
        $this->sendMessageToChannel($game,$lynchMsg);

        if ($game->hunterNeedsToShoot) {
            $this->sendMessageToChannel($game, $hunterMsg);
        }

        $game->setDayEnded(true);
        $this->changeGameState($game->getId(), GameState::NIGHT);
    }

    /**
     * @param Game $game
     */
    private function addGame(Game $game)
    {
        $this->games[$game->getId()] = $game;
    }

    /**
     * @param Game $game
     */
    private function onFirstNight(Game $game)
    {
        $client = $this->client;

        foreach ($game->getLivingPlayers() as $player) {
            $client->getDMByUserId($player->getId())
                ->then(function (DirectMessageChannel $dmc) use ($client,$player,$game) {
                    $client->send("Your role is {$player->role->getName()}", $dmc);

                    if ($player->role->isWerewolfTeam()) {
                        if (count($game->getWerewolves()) > 1) {
                            $werewolves = PlayerListFormatter::format($game->getWerewolves());
                            $client->send("The werewolves are: {$werewolves}", $dmc);
                        } else {
                            $client->send("You are the only werewolf.", $dmc);
                        }
                    }

                    if ($player->role->isRole(Role::SEER)) {
                        $client->send("Seer, select a player by saying !see #channel @username.\r\nDO NOT DISCUSS WHAT YOU SEE DURING THE NIGHT, ONLY DISCUSS DURING THE DAY IF YOU ARE NOT DEAD!", $dmc);
                    }

                    if ($player->role->isRole(Role::BEHOLDER)) {
                        $seers = $game->getPlayersOfRole(Role::SEER);
                        $seers = PlayerListFormatter::format($seers);

                        $client->send("The seer is: {$seers}", $dmc);
                    }
                });
        }
        ;
        $playerList = PlayerListFormatter::format($game->getLivingPlayers());
        $roleList = RoleListFormatter::format($game->getLivingPlayers());

        $msg = ":wolf: A new game of Werewolf is starting! For a tutorial, type !help.\r\n\r\n";
        $msg .= "Players: {$playerList}\r\n";
        $msg .= "Possible Roles: {$game->getRoleStrategy()->getRoleListMsg()}\r\n\r\n";
        $msg .= WeatherFormatter::format($game)."\r\n";
        if ($this->optionsManager->getOptionValue(OptionName::role_seer)) {
            
            $msg .= " The game will begin when the Seer chooses someone.";
        }
        $this->sendMessageToChannel($game, $msg);

        if (!$this->optionsManager->getOptionValue(OptionName::role_seer)) {
            $this->changeGameState($game->getId(), GameState::NIGHT);
        }
    }

    /**
     * @param Game $game
     */
    private function onDay(Game $game)
    {
        $remainingPlayers = PlayerListFormatter::format($game->getLivingPlayers());
        $dayBreakMsg = WeatherFormatter::format($game)."\r\n";
        $dayBreakMsg .= "Remaining Players: {$remainingPlayers}\r\n\r\n";
        $dayBreakMsg .= "Villagers, find the Werewolves! Type !vote @username to vote to lynch a player.";
        if ($this->optionsManager->getOptionValue(OptionName::changevote))
        {
            $dayBreakMsg .= "\r\nYou may change your vote at any time before voting closes. Type !vote clear to remove your vote.";
        }
        if ($this->optionsManager->getOptionValue(OptionName::no_lynch))
        {
            $dayBreakMsg .= "\r\nType !vote noone to vote to not lynch anybody today.";
        }

        $this->sendMessageToChannel($game, $dayBreakMsg);
    }

    /**
     * @param Game $game
     */
    private function onNight(Game $game)
    {
        $client = $this->client;
        $nightMsg = WeatherFormatter::format($game);
        $this->sendMessageToChannel($game, $nightMsg);

        $wolves = $game->getWerewolves();

        $wolfMsg = ":moon: It is night and it is time to hunt. Type !kill #channel @player to make your choice. ";

        foreach ($wolves as $wolf)
        {
             $this->client->getDMByUserId($wolf->getId())
                  ->then(function (DirectMessageChannel $channel) use ($client,$wolfMsg) {
                      $client->send($wolfMsg, $channel);
                  });
        }

        $seerMsg = ":crystal_ball: Seer, select a player by saying !see #channel @username.";

        $seers = $game->getPlayersOfRole(Role::SEER);

        foreach ($seers as $seer)
        {
            $this->client->getDMByUserId($seer->getId())
                 ->then(function (DirectMessageChannel $channel) use ($client,$seerMsg) {
                     $client->send($seerMsg, $channel);
                 });
        }

        $bodyGuardMsg = ":shield: Bodyguard, you may guard someone once per with your grizzled ex-lawman skills. That player cannot be eliminated. Type !guard #channel @user";

        $bodyguards = $game->getPlayersOfRole(Role::BODYGUARD);

        foreach ($bodyguards as $bodyguard) {
            $this->client->getDMByUserId($bodyguard->getId())
                 ->then(function (DirectMessageChannel $channel) use ($client,$bodyGuardMsg) {
                     $client->send($bodyGuardMsg, $channel);
                 });
        }

        $witches = $game->getPlayersOfRole(Role::WITCH);

        if (count($witches) > 0) {
            $witch_msg = ":wine_glass:  You may poison someone once for the entire game.  Type \"!poison #channel @user\" to poison someone \r\nor \"!poison #channel noone\" to do nothing.  \r\n:warning: Night will not end until you make a decision.";

            if ($game->getWitchPoisonPotion() > 0) {
                foreach ($witches as $witch) {
                    $this->client->getDMByUserId($witch->getId())
                         ->then(function (DirectMessageChannel $channel) use ($client,$witch_msg) {
                             $client->send($witch_msg, $channel);
                         });
                }
            }
            else {
                $game->setWitchPoisoned(true);
            }
        }
    }

    /**
     * @param Game $game
     */
    private function onNightEnd(Game $game)
    {
        $votes = $game->getVotes();

        $numKilled = 0;
        $hasGuarded = false;
        $hasHealed = false;
        $hasKilled = false;
        $hunterKilled = false;

        $hunterName = "";
        $killMsg = ":skull_and_crossbones: ";


		$ebolaRate = $optionsManager->getOptionValue(OptionName::ebola);

		if($ebolaRate > 0){
			if(rand(0, $ebolaRate) == 0){
				$livingPlayers = $game->getLivingPlayers();
				$playerToKill = $livingPlayers[array_rand($livingPlayers)];
				$this->sendMessageToChannel($game, ":goberserk: Ebola has struck! @{$playerToKill->getUsername()} ({$playerToKill->role->getName()}) is no longer with us.");
				$game->killPlayer($playerToKill->getId());
				$numKilled++;
			}
		}

        foreach ($votes as $lynch_id => $voters) {
            $player = $game->getPlayerById($lynch_id);

            if ($lynch_id == $game->getGuardedUserId()) {
                $hasGuarded = true;
            }
            elseif($lynch_id == $game->getWitchHealedUserId()) {
                $hasHealed = true;
            }
            else {

                $killMsg .= " @{$player->getUsername()} ({$player->role->getName()})";

                if ($player->role->isRole(Role::HUNTER)) {
                    $hunterKilled = true;
                    $hunterName = $player->getUsername();
                }

                $game->killPlayer($lynch_id);
                $hasKilled = true;
                $numKilled++;
            }
        }

        // see if witch poisoned someone
        if ($game->getWitchPoisonedUserId()) {

            $poisoned_player_id = $game->getWitchPoisonedUserId();
            $poisoned_player = $game->getPlayerById($poisoned_player_id);
            $poisoned_player_name = $poisoned_player->getUsername();
            $poisoned_player_role = (string) $poisoned_player->role->getName();

            if ($numKilled > 0) {
                $killMsg .= " and";
            }

            $killMsg .= " @{$poisoned_player->getUsername()} ($poisoned_player_role)";

            $game->killPlayer($poisoned_player_id);
            $hasKilled = true;
            $numKilled++;
            $game->setWitchPoisonedUserId(null);

            // if killed player was hunter
            if ($poisoned_player->role->isRole(Role::HUNTER)) {
                $hunterKilled = true;
                $hunterName = $poisoned_player_name;
            }
        }

        $wasOrWere = "was";
        if ($numKilled > 1) {
            $wasOrWere = "were";
        }
        $killMsg .= " $wasOrWere killed during the night.";

        $game->setLastGuardedUserId($game->getGuardedUserId());
        $game->setGuardedUserId(null);

        if ($hasKilled) {
            $this->sendMessageToChannel($game, $killMsg);

            // send shoot command to hunter if in game
            if ($hunterKilled) {

                $game->setHunterNeedsToShoot(true);
                $hunterMsg = ":bow_and_arrow: " . $hunterName . " you were killed.  The night isn't over, though, because as a hunter you can take one other player with you to your grave.  Type !shoot @playername, or !shoot noone.";
                $this->sendMessageToChannel($game, $hunterMsg);
            }
        }

        if ($numKilled == 0) {
            $this->sendMessageToChannel($game, "There was no deaths in the night!");
        }

        $game->setNightEnded(true);
    }

    /**
     * @param Game $game
     */
    private function onGameOver(Game $game)
    {
        $game->changeState(GameState::OVER);
        $this->endGame($game->getId());
    }
}
