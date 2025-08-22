<?php

class User {
    private $id;
    private $name;
    private $email;
    private $contactNumber;
    private $password;
    private $isAdmin;

    public function __construct($id, $name, $email, $contactNumber, $password, $isAdmin = false) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->contactNumber = $contactNumber;
        $this->password = $password;
        $this->isAdmin = $isAdmin;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getContactNumber() {
        return $this->contactNumber;
    }

    public function getPassword() {
        return $this->password;
    }

    public function isAdmin() {
        return $this->isAdmin;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setContactNumber($contactNumber) {
        $this->contactNumber = $contactNumber;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function setAdmin($isAdmin) {
        $this->isAdmin = $isAdmin;
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'contactNumber' => $this->contactNumber,
            'isAdmin' => $this->isAdmin,
        ];
    }
}