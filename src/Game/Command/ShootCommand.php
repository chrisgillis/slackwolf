<?php namespace Slackwolf\Game\Command;

use Exception;
use InvalidArgumentException;
use Slack\Channel;
use Slack\ChannelInterface;
use Slack\DirectMessageChannel;
use Slack\RealTimeClient;
use Slackwolf\Game\Formatter\ChannelIdFormatter;
use Slackwolf\Game\Formatter\KillFormatter;
use Slackwolf\Game\Formatter\UserIdFormatter;
use Slackwolf\Game\Game;
use Slackwolf\Game\GameManager;
use Slackwolf\Game\GameState;
use Slackwolf\Game\Role;
use Slackwolf\Game\OptionsManager;
use Slackwolf\Game\OptionName;
use Slackwolf\Message\Message;

/**
 * Defines the ShootCommand class.
 */
class ShootCommand extends Command
{

    /**
     * {@inheritdoc}
     *
     * Constructs a new Shoot command.
     */
    public function __construct(RealTimeClient $client, GameManager $gameManager, Message $message, array $args = null)
    {
        parent::__construct($client, $gameManager, $message, $args);

        $client = $this->client;

        if ( ! $this->game) {
            throw new Exception("No game in progress.");
        }

        if (!$this->game->hunterNeedsToShoot) {
          $this->gameManager->sendMessageToChannel($this->game, ":warning: Invalid !shoot command.");
          throw new Exception("Hunter cant shoot yet.");
        }

        if ($this->channel[0] == 'D') {
            $this->gameManager->sendMessageToChannel($this->game, "Please !shoot in the public channel.");
            throw new Exception("You may not !shoot privately.");
        }

        if (count($this->args) < 1) {
          $this->gameManager->sendMessageToChannel($this->game, "Please target a player using !shoot @player");
          throw new InvalidArgumentException("Must specify a player");
        }

        $this->args[0] = UserIdFormatter::format($this->args[0], $this->game->getOriginalPlayers());
    }

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        $client = $this->client;

        // Person should be hunter
        $player = $this->game->getPlayerById($this->userId);

        if (!$player->role || !$player->role->isRole(Role::HUNTER)) {
            $this->gameManager->sendMessageToChannel($this->game, ":warning: Invalid !shoot command.");
            throw new Exception("Only hunter can shoot.");
        }

        // Hunter should be dead to shoot
        if ( $this->game->isPlayerAlive($this->userId)) {
            $this->gameManager->sendMessageToChannel($this->game, ":warning: Invalid !shoot command.");
            throw new Exception("Can't shoot if alive.");
        }

        if ($this->args[0] == 'noone') {
            $this->game->setHunterNeedsToShoot(false);
            $this->gameManager->sendMessageToChannel($this->game,
              ":bow_and_arrow: " . $player->getUsername() .
                  " (Hunter) decided not to shoot anyone, and died.");
        }
        else {

          $targeted_player_id = $this->args[0];

          // Person player is shooting should be alive
          if ( ! $this->game->isPlayerAlive($targeted_player_id)) {
              $this->gameManager->sendMessageToChannel($this->game,
                ":warning: Targetted player is not in game or dead.");

              throw new Exception("Voted player not found in game.");
          }

          $targeted_player = $this->game->getPlayerById($targeted_player_id);
          $this->game->killPlayer($targeted_player_id);
          $this->game->setHunterNeedsToShoot(false);

          $this->gameManager->sendMessageToChannel($this->game,
                ":bow_and_arrow: " . $player->getUsername() .
                " (Hunter) shot dead " . $targeted_player->getUsername() .
                " (" . $targeted_player->role->getName() . "), and then died.");
        }

        if ($this->game->getState() == GameState::DAY) {
          $this->gameManager->changeGameState($this->game->getId(), GameState::NIGHT);
        }
        else {
          $this->gameManager->changeGameState($this->game->getId(), GameState::DAY);
        }
    }
}
