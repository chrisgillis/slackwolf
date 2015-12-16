<?php namespace Slackwolf;
/**
 * Game.php
 *
 * @company StitchLabs
 * @project slackwolf
 *
 * @author  Chris Gillis
 */

/**
 * Class Game
 */
class Game
{
    protected $channel;
    protected $players = [];
    protected $state;
    protected $votes = [];
    protected $winning_team;

    public function __construct($channel, $players)
    {
        $this->channel = $channel;

        /** @var $players \Slack\User[] */
        foreach ($players as $player) {
            if ($player->getUsername() == getenv('BOT_NAME')) {
                continue;
            }

            $this->players[$player->getId()] = $player;
        }

        $roles = ['Seer'];

        $numWerewolves = ceil(count($this->players) / 4);

        for($i = 0; $i < $numWerewolves; $i++) {
            $roles[] = 'Werewolf';
        }

        $numVillagers = abs(count($this->players) - count($roles));

        for ($i = 0; $i < $numVillagers; $i++) {
            $roles[] = 'Villager';
        }

        shuffle($roles);

        $i = 0;
        foreach ($this->players as $id => $player) {
            $this->players[$id]->role = $roles[$i];
            $i++;
        }

        $this->state = 'setup';
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function setPlayers($players)
    {
        $this->players = $players;
    }

    public function removePlayer($player_id)
    {
        if (isset($this->players[$player_id])) {
            unset($this->players[$player_id]);
        }
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function clearVotes()
    {
        $this->votes = [];
    }


    public function addVote($for, $by) {
        if ( ! isset($this->votes[$for])) {
            $this->votes[$for] = [];
        }

        $this->votes[$for][] = $by;
    }

    public function hasAlreadyVoted($user_id) {
        foreach ($this->votes as $lynch_id => $users) {
            foreach ($users as $user) {
                if ($user->getId() == $user_id) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getVotes() {
        return $this->votes;
    }

    public function getNumWerewolves()
    {
        $num_werewolves = 0;

        foreach($this->players as $player) {
            if ($player->role == 'Werewolf') {
                $num_werewolves++;
            }
        }

        return $num_werewolves;
    }

    public function getNumVillagers()
    {
        $num_villagers = 0;

        foreach($this->players as $player) {
            if ($player->role == 'Villager' || $player->role == 'Seer') {
                $num_villagers++;
            }
        }

        return $num_villagers;
    }

    public function isOver()
    {
        $num_villagers = 0;
        $num_werewolves = 0;

        foreach($this->players as $player) {
            if ($player->role == 'Villager' || $player->role == 'Seer') {
                $num_villagers++;
            }

            if ($player->role == 'Werewolf') {
                $num_werewolves++;
            }
        }

        if($num_werewolves == 0) {
            $this->winning_team = 'Villagers';
            return true;
        }

        if ($num_werewolves >= $num_villagers) {
            $this->winning_team = 'Werewolves';
            return true;
        }

        return false;
    }

    public function whoWon()
    {
        return $this->winning_team;
    }
}