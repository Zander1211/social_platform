<?php

namespace Model;

class Reaction
{
    private $id;
    private $userId;
    private $postId;
    private $commentId;
    private $reactionType; // e.g., Like, Haha, Heart, Sad, Angry
    private $createdAt;

    public function __construct($userId, $postId = null, $commentId = null, $reactionType)
    {
        $this->userId = $userId;
        $this->postId = $postId;
        $this->commentId = $commentId;
        $this->reactionType = $reactionType;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getPostId()
    {
        return $this->postId;
    }

    public function getCommentId()
    {
        return $this->commentId;
    }

    public function getReactionType()
    {
        return $this->reactionType;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}