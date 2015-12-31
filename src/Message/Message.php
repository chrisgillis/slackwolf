<?php namespace Slackwolf\Message;

class Message
{
    private $text;
    private $channel;
    private $user;
    private $subType;

    public function __construct($data = [])
    {
        $this->text = isset($data['text']) ? $data['text'] : null;
        $this->channel = isset($data['channel']) ? $data['channel'] : null;
        $this->user = isset($data['user']) ? $data['user'] : null;
        $this->subType = isset($data['subtype']) ? $data['subtype'] : null;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return mixed
     */
    public function getSubType()
    {
        return $this->subType;
    }

    /**
     * @param mixed $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return mixed
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param mixed $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
}