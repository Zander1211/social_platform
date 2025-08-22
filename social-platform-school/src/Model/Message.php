<?php

namespace Model;

class Message
{
    private $id;
    private $senderId;
    private $receiverId;
    private $content;
    private $timestamp;

    public function __construct($senderId, $receiverId, $content)
    {
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->content = $content;
        $this->timestamp = time();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSenderId()
    {
        return $this->senderId;
    }

    public function getReceiverId()
    {
        return $this->receiverId;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}