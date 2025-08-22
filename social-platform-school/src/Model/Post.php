<?php

namespace Model;

use Database\DatabaseConnection;

class Post
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseConnection::getInstance();
    }

    public function createPost($data)
    {
        $query = "INSERT INTO posts (caption, user_id, created_at) VALUES (:caption, :user_id, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':caption', $data['caption']);
        $stmt->bindParam(':user_id', $data['user_id']);
        return $stmt->execute();
    }

    public function editPost($postId, $data)
    {
        $query = "UPDATE posts SET caption = :caption WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':caption', $data['caption']);
        $stmt->bindParam(':id', $postId);
        return $stmt->execute();
    }

    public function deletePost($postId)
    {
        $query = "DELETE FROM posts WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $postId);
        return $stmt->execute();
    }

    public function getPost($postId)
    {
        $query = "SELECT * FROM posts WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $postId);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getAllPosts()
    {
        $query = "SELECT * FROM posts ORDER BY created_at DESC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }
}