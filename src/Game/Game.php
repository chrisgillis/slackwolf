<?php namespace Slackwolf\Game;

use Slackwolf\Game\RoleStrategy\RoleStrategyInterface;

class Game
{
    private $id;
    private $state;
    private $players = [];
    private $originalPlayers = [];
    private $votes = [];
    private $winningTeam;
    private $seerSeen;
    private $wolvesVoted;

    /**
     * @param                       $id
     * @param \Slack\User[]         $users
     * @param RoleStrategyInterface $roleStrategy
     */
    public function __construct($id, array $users, RoleStrategyInterface $roleStrategy)
    {
        $this->id = $id;

        $players = $roleStrategy->assign($users);

        foreach ($players as $player) {
            $this->players[$player->getId()] = $player;
            $this->originalPlayers[$player->getId()] = $player;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Slack\User[]
     */
    public function getPlayers()
    {
        return $this->players;
    }

    public function removePlayer($player_id)
    {
        unset($this->players[$player_id]);
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
        $werewolves = [];

        foreach ($this->players as $player) {
            if ($player->role == $roleType) {
                $werewolves[] = $player;
            }
        }

        return $werewolves;
    }

    public function hasPlayer($playerId) {
        return isset($this->players[$playerId]);
    }

    /**
     * @param $id
     *
     * @return \Slack\User|bool
     */
    public function getPlayerById($id) {
        if($this->hasPlayer($id)) {
            return $this->players[$id];
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

    public function votingFinished()
    {
        foreach ($this->players as $player) {
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
        $numSeers = $this->getNumRole(Role::SEER);
        $numVillagers = $this->getNumRole(Role::VILLAGER);
        $numWerewolves = $this->getNumRole(Role::WEREWOLF);

        $numGood = $numVillagers + $numSeers;

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


}