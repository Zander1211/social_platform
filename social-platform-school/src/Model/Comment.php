<?php

namespace Model;

class Comment {
    private $id;
    private $postId;
    private $userId;
    private $content;
    private $createdAt;
    private $updatedAt;

    public function __construct($postId, $userId, $content) {
        $this->postId = $postId;
        $this->userId = $userId;
        $this->content = $content;
        $this->createdAt = date('Y-m-d H:i:s');
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function getId() {
        return $this->id;
    }

    public function getPostId() {
        return $this->postId;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getContent() {
        return $this->content;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    public function setContent($content) {
        $this->content = $content;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;
    }
}