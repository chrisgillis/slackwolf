<?php namespace Slackwolf;

    /**
     * SlackRTMClient.php
     *
     * @company StitchLabs
     * @project slackwolf2
     *
     * @author  Chris Gillis
     */
use Slack\Channel;
use Slack\Payload;
use Slack\RealTimeClient;

use GuzzleHttp;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

use Slackwolf\Message\Message;

/**
 * Class SlackRTMClient
 *
 * @package Slackwolf
 */
class SlackRTMClient extends RealTimeClient
{

    public function connect()
    {
        /*
         * Call the parent method
         */
        $deferred = parent::connect();

        /* Queue our addition of the time onto the promise once its done.
         */
        $deferred->then(function() {
                /*
                 * If a pong response is returned down the pipes set the flag to true.
                 */
                echo "Pong Capture setup...\r\n";
                $this->on('pong', function ($data) {
                    $this->pong_response = True;
                });
        })->then(function () {
            $this->loop->addPeriodicTimer(5, function ($timer) {
                /*
                 * Check if a liveness test has been called before and look the the
                 * result.
                 */
                $this->reconnecting = False;
                if(property_exists($this, 'pong_response')){
                    if (!$this->pong_response){
                        echo "Pong Not Recived...\r\n";
                        echo "Attempting to reconnet...\r\n";
                        $this->reconnecting = True;
                        /*
                         * We think this websocket is dead so try and close it
                         * from the  client end.
                         */
                        $this->websocket->close();
                        /*
                         * Recall the connect function on the slack API. And reset
                         * out state to the initial state.
                         */
                        parent::connect()->then(function() {
                            echo "Reconnected.\n";
                            $this->reconnecting = False;
                            unset($this->pong_response);
                        }, function(ConnectionException $e) {
                            echo $e->getMessage();
                            exit();
                        });

                    }
                }
                /*
                 * Send the ping request down the web socket in order to check the
                 * liveness.
                 */
                if(!$this->reconnecting){
                    $data = [
                        'id' => ++$this->lastMessageId,
                        'type' => 'ping',
                    ];
                    $this->pong_response = False;
                    $this->websocket->send(json_encode($data));
                }
            });

        });

        return $deferred;

    }
    /**
     * @param $channelId
     */
    public function refreshChannel($channelId)
    {
        /** @var Channel $channel */
        $this->apiCall('channels.info', [
            'channel' => $channelId,
        ])->then(function (Payload $response) {
            $channel = new Channel($this, $response['channel']);
            $this->channels[$channel->getId()] = $channel;
        });
    }

}
