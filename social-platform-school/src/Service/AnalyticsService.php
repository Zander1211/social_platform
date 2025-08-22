<?php

namespace App\Service;

use App\Model\Post;
use App\Model\User;

class AnalyticsService
{
    protected $postModel;
    protected $userModel;

    public function __construct()
    {
        $this->postModel = new Post();
        $this->userModel = new User();
    }

    public function getPostViews($postId)
    {
        // Logic to retrieve the number of views for a specific post
        return $this->postModel->getViews($postId);
    }

    public function getActiveUsersCount()
    {
        // Logic to count the number of active users
        return $this->userModel->countActiveUsers();
    }

    public function getPostEngagement($postId)
    {
        // Logic to retrieve engagement metrics for a specific post
        $views = $this->getPostViews($postId);
        $reactions = $this->postModel->getReactionsCount($postId);
        $comments = $this->postModel->getCommentsCount($postId);

        return [
            'views' => $views,
            'reactions' => $reactions,
            'comments' => $comments,
        ];
    }
}