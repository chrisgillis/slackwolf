<?php namespace Slackwolf\Game;

use Slackwolf\Game\RoleStrategy\RoleStrategyInterface;

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
    public $nightEnded;
    public $hunterNeedsToShoot;
    public $seerSeen;
    public $wolvesVoted;
    public $witchHealed;
    public $witchPoisoned;

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
    }

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

    public function getRoleStrategy()
    {
        return $this->roleStrategy;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Slack\User[]
     */
    public function getLobbyPlayers()
    {
        return $this->lobbyPlayers;
    }

    public function addLobbyPlayer($user)
    {
        if ($this->state == GameState::LOBBY) {
            $player_id = $user->getId();
            if (! isset($this->lobbyPlayers[$player_id])){
                $this->lobbyPlayers[$player_id] =$user;
            }
        }
    }

    public function removeLobbyPlayer($player_id)
    {
        unset($this->lobbyPlayers[$player_id]);
    }

    /**
     * @return \Slack\User[]
     */
    public function getLivingPlayers()
    {
        return $this->livingPlayers;
    }

    /**
     * @return \Slack\User[]
     */
    public function getDeadPlayers()
    {
        return $this->deadPlayers;
    }

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

    public function getState()
    {
        return $this->state;
    }

    public function getVotes()
    {
        return $this->votes;
    }

    public function vote($voterId, $voteForId)
    {
        if ( ! isset($this->votes[$voteForId])) {
            $this->votes[$voteForId] = [];
        }

        $this->votes[$voteForId][] = $voterId;
    }

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

    public function votingFinished()
    {
        foreach ($this->livingPlayers as $player) {
            if ( ! $this->hasPlayerVoted($player->getId())) {
                return false;
            }
        }

        return true;
    }

    public function clearVotes()
    {
        $this->votes = [];
    }

    public function isOver()
    {
        $numWerewolves = count($this->getWerewolves());
        $numTanner = $this->getNumRole(Role::TANNER);

        $numGood = count($this->getLivingPlayers()) - $numWerewolves;

        if ($numTanner == 0) {
            if ($this->getOriginalNumRole(Role::TANNER) > 0) {
                $this->winningTeam = Role::TANNER;
                return true;
            }
        }

        if ($numWerewolves == 0) {
            $this->winningTeam = Role::VILLAGER;
            return true;
        }

        if ($numWerewolves >= $numGood) {
            $this->winningTeam = Role::WEREWOLF;
            return true;
        }

        return false;
    }

    public function whoWon()
    {
        return $this->winningTeam;
    }

    public function seerSeen()
    {
        return $this->seerSeen;
    }

    public function setSeerSeen($seen)
    {
        $this->seerSeen = $seen;
    }

    public function changeState($state) {
        $this->state = $state;
        $this->clearVotes();
        $this->seerSeen = false;
        $this->wolvesVoted = false;
        $this->witchHealed = false;
        $this->witchPoisoned = false;

        $this->setNightEnded(false);
        $this->setWitchHealedUserId(null);
        $this->setWitchPoisonedUserId(null);
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

    public function getGuardedUserId()
    {
        return $this->guardedUserId;
    }

    public function setGuardedUserId($id)
    {
        $this->guardedUserId = $id;
    }

    public function getLastGuardedUserId()
    {
        return $this->lastGuardedUserId;
    }

    public function setLastGuardedUserId($id)
    {
        $this->lastGuardedUserId = $id;
    }

    public function getWitchHealingPotion() {
        return $this->witchHealingPotion;
    }

    public function setWitchHealingPotion($val) {
        $this->witchHealingPotion = $val;
    }

    public function getWitchPoisonPotion() {
        return $this->witchPoisonPotion;
    }

    public function setWitchPoisonPotion($val) {
        $this->witchPoisonPotion = $val;
    }

    public function getWitchHealed()
    {
        return $this->witchHealed;
    }

    public function setWitchHealed($healed)
    {
        $this->witchHealed = $healed;
    }

    public function getWitchPoisoned()
    {
        return $this->witchPoisoned;
    }

    public function setWitchPoisoned($poisoned)
    {
        $this->witchPoisoned = $poisoned;
    }

    public function getWitchHealedUserId() {
        return $this->witchHealedUserId;
    }

    public function setWitchHealedUserId($id) {
        $this->witchHealedUserId = $id;
    }

    public function getWitchPoisonedUserId() {
        return $this->witchPoisonedUserId;
    }

    public function setWitchPoisonedUserId($id) {
        $this->witchPoisonedUserId = $id;
    }

}