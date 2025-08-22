<?php

namespace App\Service;

use App\Model\User;
use App\Model\Post;

class NotificationService
{
    protected $users;
    protected $posts;

    public function __construct()
    {
        $this->users = new User();
        $this->posts = new Post();
    }

    public function sendNotification($userId, $message)
    {
        // Logic to send notification to a user
        // This could involve saving the notification to the database
        // and/or sending a real-time notification via WebSocket or similar
    }

    public function getUserNotifications($userId)
    {
        // Logic to retrieve notifications for a specific user
        // This could involve querying the database for notifications related to the user
    }

    public function notifyPostCreation($postId)
    {
        // Logic to notify users about a new post
        $post = $this->posts->find($postId);
        $users = $this->users->getAllUsers();

        foreach ($users as $user) {
            $this->sendNotification($user->id, "A new post has been created: " . $post->title);
        }
    }

    public function notifyComment($postId, $commenterId)
    {
        // Logic to notify users about a new comment on a post
        $post = $this->posts->find($postId);
        $users = $this->users->getUsersSubscribedToPost($postId);

        foreach ($users as $user) {
            if ($user->id !== $commenterId) {
                $this->sendNotification($user->id, "User " . $commenterId . " commented on a post you follow: " . $post->title);
            }
        }
    }
}