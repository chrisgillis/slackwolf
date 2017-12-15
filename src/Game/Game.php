<?php namespace Slackwolf\Game;

use Slack\User;
use Slackwolf\Game\RoleStrategy\RoleStrategyInterface;

/**
 * Defines the Game class.
 * @package Slackwolf\Game
 */
class Game
{
    private $id;
    private $lobbyPlayers = [];
    private $livingPlayers = [];
    private $deadPlayers = [];
    private $originalPlayers = [];
    private $votes = [];
    private $winningTeam;

    private $guardedUserId;
    private $lastGuardedUserId;
    private $witchHealingPotion = 0;
    private $witchPoisonPotion = 0;

    private $witchHealedUserId;
    private $witchPoisonedUserId;
    private $roleStrategy;
    private $optionsManager;

    public $state;
    public $dayEnded;
    public $nightEnded;
    public $hunterNeedsToShoot;
    public $seerSeen;
    public $foolSeen;
    public $wolvesVoted;
    public $witchHealed;
    public $witchPoisoned;
    public $tannerWin;
    

    /**
     * @param                       $id
     * @param \Slack\User[]         $users
     * @param RoleStrategyInterface $roleStrategy
     */
    public function __construct($id, array $users, RoleStrategyInterface $roleStrategy)
    {
        $this->id = $id;
        $this->roleStrategy = $roleStrategy;
        $this->optionsManager = new OptionsManager();
        $this->state = GameState::LOBBY;
        $this->lobbyPlayers = $users;
	$this->tannerWin = false;
    }

    /**
     * Assigns each user in the game to a role.
     */
    public function assignRoles() {
        $players = $this->roleStrategy->assign($this->lobbyPlayers, $this->optionsManager);

        foreach ($players as $player) {
            $this->livingPlayers[$player->getId()] = $player;
            $this->originalPlayers[$player->getId()] = $player;

            if ($player->role->isRole(Role::WITCH)) {
                $this->setWitchHealingPotion(1);
                $this->setWitchPoisonPotion(1);
            }
        }
    }

    /**
     * @return RoleStrategyInterface
     *   The game's strategy type.
     */
    public function getRoleStrategy()
    {
        return $this->roleStrategy;
    }

    /**
     * @return string
     *   The game's ID, matches the channel ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Slack\User[]
     *   An array of the lobby members.
     */
    public function getLobbyPlayers()
    {
        return $this->lobbyPlayers;
    }

    /**
     * If the lobby is open, adds a user to it.
     *
     * @param User $user
     *   The user to add to the lobby.
     *
     * @return bool
     *   If successful, returns TRUE, otherwise, FALSE.
     */
    public function addLobbyPlayer(User $user)
    {
        if ($this->state == GameState::LOBBY) {
            $player_id = $user->getId();
            if (! isset($this->lobbyPlayers[$player_id])){
                $this->lobbyPlayers[$player_id] = $user;
                return TRUE;
            }
        }
    }

    /**
     * Removes a user from the game lobby.
     *
     * @param $player_id
     *   The user to remove from the lobby.
     *
     * @return bool
     *   If successful, returns TRUE, otherwise, FALSE.
     */
    public function removeLobbyPlayer($player_id)
    {
        if (isset($this->lobbyPlayers[$player_id])) {
            unset($this->lobbyPlayers[$player_id]);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * An array containing all users that are still alive.
     *
     * @return \Slack\User[]
     *   Users who are currently alive.
     */
    public function getLivingPlayers()
    {
        return $this->livingPlayers;
    }

    /**
     * An array containing all the users that have been killed.
     *
     * @return \Slack\User[]
     *   Users who have been killed.
     */
    public function getDeadPlayers()
    {
        return $this->deadPlayers;
    }

    /**
     * Kills the specified player.
     *
     * @param $player_id
     *   The player to kill.
     */
    public function killPlayer($player_id)
    {
        if (isset($this->livingPlayers[$player_id])) {
            $player = $this->livingPlayers[$player_id];
            unset($this->livingPlayers[$player_id]);
            $this->deadPlayers[$player_id] = $player;
        }
    }

    /**
     * @return \Slack\User[]
     */
    public function getOriginalPlayers()
    {
        return $this->originalPlayers;
    }

    /**
     * @return \Slack\User[]
     */
    public function getPlayersOfRole($roleType)
    {
        $playersofRole = [];

        foreach ($this->livingPlayers as $player) {
            if ($player->role->isRole($roleType)) {
                $playersofRole[] = $player;
            }
        }

        return $playersofRole;
    }

    /**
     * @return \Slack\User[]
     */
    public function getWerewolves()
    {
        $werewolves = [];

        foreach ($this->livingPlayers as $player) {
            if ($player->role->isWerewolfTeam()) {
                $werewolves[] = $player;
            }
        }

        return $werewolves;
    }

    /**
     * @return \Slack\User[]
     */
    public function getVillageTeam()
    {
        $villagers = [];

        foreach ($this->livingPlayers as $player) {
            if (!$player->role->isWerewolfTeam()) {
                $villagers[] = $player;
            }
        }

        return $villagers;
    }

    /**
     * @return \Slack\User[]
     */
    public function getOriginalPlayersOfRole($roleType)
    {
        $originalPlayersOfRole = [];

        foreach ($this->originalPlayers as $player) {
            if ($player->role->isRole($roleType)) {
                $originalPlayersOfRole[] = $player;
            }
        }

        return $originalPlayersOfRole;
    }

    /**
     * Whether or not the player is alive.
     *
     * @param $playerId
     *   The Slack user ID.
     *
     * @return bool
     *   TRUE if player is alive and in game, otherwise FALSE.
     */
    public function isPlayerAlive($playerId)
    {
        return isset($this->livingPlayers[$playerId]);
    }

    /**
     * @param $id
     *
     * @return \Slack\User|bool
     */
    public function getPlayerById($id)
    {
        if (isset($this->originalPlayers[$id])) {
            return $this->originalPlayers[$id];
        }

        return false;
    }

    /**
     * @return int
     */
    public function getNumRole($roleType)
    {
        return count($this->getPlayersOfRole($roleType));
    }

    /**
     * @return int
     */
    public function getOriginalNumRole($roleType)
    {
        return count($this->getOriginalPlayersOfRole($roleType));
    }

    /**
     * @return int
     *   The state of the game.
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return array
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * @param $voterId
     * @param $voteForId
     */
    public function vote($voterId, $voteForId)
    {
        if ( ! isset($this->votes[$voteForId])) {
            $this->votes[$voteForId] = [];
        }

        $this->votes[$voteForId][] = $voterId;
    }

    /**
     * @param $voterId
     *
     * @return bool
     */
    public function hasPlayerVoted($voterId)
    {
        foreach ($this->votes as $voted => $voters)
        {
            foreach ($voters as $voter)
            {
                if ($voter == $voterId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $voterId
     */
    public function clearPlayerVote($voterId)
    {
        foreach ($this->votes as $voted => $voters)
        {
            foreach ($voters as $voterKey => $voter)
            {
                if ($voter == $voterId) {
                    //Remove voter
                    unset($this->votes[$voted][$voterKey]);
                    //Clear empty arrays
                    if (count($this->votes[$voted]) == 0) {
                        unset($this->votes[$voted]);
                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function votingFinished()
    {
        foreach ($this->livingPlayers as $player) {
            if ( ! $this->hasPlayerVoted($player->getId())) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     */
    public function clearVotes()
    {
        $this->votes = [];
    }

    /**
     * @return bool
     */
    public function isOver()
    {
        $numWerewolves = count($this->getWerewolves());
        $numTanner = $this->getNumRole(Role::TANNER);

        $numGood = count($this->getLivingPlayers()) - $numWerewolves;
       
	if ($this->tannerWin == true) {
		$this->winningTeam = Role::TANNER;
		return true;
	}
 
        if ($numWerewolves == 0) {
            $this->winningTeam = Role::VILLAGER;
            return true;
        }

        if ($numWerewolves >= $numGood) {
            $this->winningTeam = Role::WEREWOLF;
            return true;
        }
	
	/*if ($numTanner == 0) {
            if ($this->getOriginalNumRole(Role::TANNER) > 0 && $this->getState() == GameState::DAY ) {
                $this->winningTeam = Role::TANNER;
                return true;
            }
        }*/

        return false;
    }

    /**
     * @return mixed
     */
    public function whoWon()
    {
        return $this->winningTeam;
    }

    /**
     * @return mixed
     */
    public function seerSeen()
    {
        return $this->seerSeen;
    }

    /**
     * @return mixed
     */
    public function foolSeen()
    {
        return $this->foolSeen;
    }

    /**
     * @param $seen
     */
    public function setSeerSeen($seen)
    {
        $this->seerSeen = $seen;
    }

    /**
     * @param $seen
     */
    public function setFoolSeen($seen)
    {
        $this->foolSeen = $seen;
    }

    /**
     * @param $state
     */
    public function changeState($state) {
        $this->state = $state;
        $this->clearVotes();
        $this->seerSeen = false;
        $this->foolSeen = false;
        $this->wolvesVoted = false;
        $this->witchHealed = false;
        $this->witchPoisoned = false;

        $this->setDayEnded(false);
        $this->setNightEnded(false);
        $this->setWitchHealedUserId(null);
        $this->setWitchPoisonedUserId(null);
    }

    /**
     * @param bool $val
     */
    public function setDayEnded($val) {
        $this->dayEnded = $val;
    }

    /**
     * @param bool $val
     */
    public function setNightEnded($val) {
        $this->nightEnded = $val;
    }

    /**
     * @param bool $val
     */
    public function setHunterNeedsToShoot($val) {
        $this->hunterNeedsToShoot = $val;
    }

    /**
     * @return mixed
     */
    public function getWolvesVoted()
    {
        return $this->wolvesVoted;
    }

    /**
     * @param mixed $wolvesVoted
     */
    public function setWolvesVoted($wolvesVoted)
    {
        $this->wolvesVoted = $wolvesVoted;
    }

    /**
     * @return mixed
     */
    public function getGuardedUserId()
    {
        return $this->guardedUserId;
    }

    /**
     * @param $id
     */
    public function setGuardedUserId($id)
    {
        $this->guardedUserId = $id;
    }

    /**
     * @return mixed
     */
    public function getLastGuardedUserId()
    {
        return $this->lastGuardedUserId;
    }

    /**
     * @param $id
     */
    public function setLastGuardedUserId($id)
    {
        $this->lastGuardedUserId = $id;
    }

    /**
     * @return int
     */
    public function getWitchHealingPotion() {
        return $this->witchHealingPotion;
    }

    /**
     * @param $val
     */
    public function setWitchHealingPotion($val) {
        $this->witchHealingPotion = $val;
    }

    /**
     * @return int
     */
    public function getWitchPoisonPotion() {
        return $this->witchPoisonPotion;
    }

    /**
     * @param $val
     */
    public function setWitchPoisonPotion($val) {
        $this->witchPoisonPotion = $val;
    }

    /**
     * @return mixed
     */
    public function getWitchHealed()
    {
        return $this->witchHealed;
    }

    /**
     * @param $healed
     */
    public function setWitchHealed($healed)
    {
        $this->witchHealed = $healed;
    }

    /**
     * @return mixed
     */
    public function getWitchPoisoned()
    {
        return $this->witchPoisoned;
    }

    /**
     * @param $poisoned
     */
    public function setWitchPoisoned($poisoned)
    {
        $this->witchPoisoned = $poisoned;
    }

    /**
     * @return mixed
     */
    public function getWitchHealedUserId() {
        return $this->witchHealedUserId;
    }

    /**
     * @param $id
     */
    public function setWitchHealedUserId($id) {
        $this->witchHealedUserId = $id;
    }

    /**
     * @return mixed
     */
    public function getWitchPoisonedUserId() {
        return $this->witchPoisonedUserId;
    }

    /**
     * @param $id
     */
    public function setWitchPoisonedUserId($id) {
        $this->witchPoisonedUserId = $id;
    }

    /**
     * @param $gameMode
     * @return bool
     */
    public function isGameMode($gameMode) {
        return $this->optionsManager->isGameMode($gameMode);
    }

}
