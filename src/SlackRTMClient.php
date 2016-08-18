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