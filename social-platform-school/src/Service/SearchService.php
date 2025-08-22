<?php

namespace App\Service;

use App\Model\User;
use App\Model\Post;

class SearchService
{
    protected $userModel;
    protected $postModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->postModel = new Post();
    }

    public function searchUsers($query)
    {
        return $this->userModel->search($query);
    }

    public function searchPosts($query)
    {
        return $this->postModel->search($query);
    }
}