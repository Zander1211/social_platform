<?php

namespace Model;

class Event {
    private $id;
    private $title;
    private $description;
    private $date;
    private $time;
    private $location;
    private $createdBy;

    public function __construct($title, $description, $date, $time, $location, $createdBy) {
        $this->title = $title;
        $this->description = $description;
        $this->date = $date;
        $this->time = $time;
        $this->location = $location;
        $this->createdBy = $createdBy;
    }

    public function getId() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getDate() {
        return $this->date;
    }

    public function getTime() {
        return $this->time;
    }

    public function getLocation() {
        return $this->location;
    }

    public function getCreatedBy() {
        return $this->createdBy;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setDate($date) {
        $this->date = $date;
    }

    public function setTime($time) {
        $this->time = $time;
    }

    public function setLocation($location) {
        $this->location = $location;
    }

    public function setCreatedBy($createdBy) {
        $this->createdBy = $createdBy;
    }
}