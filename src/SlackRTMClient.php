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

/**
 * Class SlackRTMClient
 *
 * @package Slackwolf
 */
class SlackRTMClient extends RealTimeClient
{
    public function __construct(LoopInterface $loop, GuzzleHttp\ClientInterface $httpClient = null)
    {
        /*
         * Call the parent constructor
         */
        parent::__construct($loop, $httpClient);

        $this->loop->addPeriodicTimer(5, function () {
            /*
             * Check if a liveness test has been called before and look the the
             * result.
             */
            if(property_exists($this, 'pong_response')){
                if($this->pong_response){
                    echo "Pong Acknowledged...\r\n";
                } else{
                    echo "Pong Missing...\r\n";
                }
            }
            /*
             * Send the ping request down the web socket in order to check the
             * liveness.
             */
            $data = [
                'id' => ++$this->lastMessageId,
                'type' => 'ping',
            ];
            $this->pong_response = False;
            $this->websocket->send(json_encode($data));
        });

        /*
         * If a pong response is returned down the pipes set the flag to true.
         */
        $this->websocket->on('pong', function ($message) {
            $this->pong_response = True;
        });


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
