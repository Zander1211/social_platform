<?php

class User {
    private $id;
    private $name;
    private $email;
    private $contactNumber;
    private $password;
    private $isAdmin;
    private $studentId;
    private $dateOfBirth;
    private $gender;
    private $yearLevel;
    private $course;
    private $major;
    private $bio;
    private $hometown;
    private $interests;
    private $emergencyContactName;
    private $emergencyContactPhone;
    private $profileVisibility;

    public function __construct($id, $name, $email, $contactNumber, $password, $isAdmin = false, 
                              $studentId = null, $dateOfBirth = null, $gender = null, $yearLevel = null, 
                              $course = null, $major = null, $bio = null, $hometown = null, 
                              $interests = null, $emergencyContactName = null, $emergencyContactPhone = null, 
                              $profileVisibility = 'students_only') {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->contactNumber = $contactNumber;
        $this->password = $password;
        $this->isAdmin = $isAdmin;
        $this->studentId = $studentId;
        $this->dateOfBirth = $dateOfBirth;
        $this->gender = $gender;
        $this->yearLevel = $yearLevel;
        $this->course = $course;
        $this->major = $major;
        $this->bio = $bio;
        $this->hometown = $hometown;
        $this->interests = $interests;
        $this->emergencyContactName = $emergencyContactName;
        $this->emergencyContactPhone = $emergencyContactPhone;
        $this->profileVisibility = $profileVisibility;
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

    // Getters for new fields
    public function getStudentId() {
        return $this->studentId;
    }

    public function getDateOfBirth() {
        return $this->dateOfBirth;
    }

    public function getGender() {
        return $this->gender;
    }

    public function getYearLevel() {
        return $this->yearLevel;
    }

    public function getCourse() {
        return $this->course;
    }

    public function getMajor() {
        return $this->major;
    }

    public function getBio() {
        return $this->bio;
    }

    public function getHometown() {
        return $this->hometown;
    }

    public function getInterests() {
        return $this->interests;
    }

    public function getEmergencyContactName() {
        return $this->emergencyContactName;
    }

    public function getEmergencyContactPhone() {
        return $this->emergencyContactPhone;
    }

    public function getProfileVisibility() {
        return $this->profileVisibility;
    }

    // Setters for new fields
    public function setStudentId($studentId) {
        $this->studentId = $studentId;
    }

    public function setDateOfBirth($dateOfBirth) {
        $this->dateOfBirth = $dateOfBirth;
    }

    public function setGender($gender) {
        $this->gender = $gender;
    }

    public function setYearLevel($yearLevel) {
        $this->yearLevel = $yearLevel;
    }

    public function setCourse($course) {
        $this->course = $course;
    }

    public function setMajor($major) {
        $this->major = $major;
    }

    public function setBio($bio) {
        $this->bio = $bio;
    }

    public function setHometown($hometown) {
        $this->hometown = $hometown;
    }

    public function setInterests($interests) {
        $this->interests = $interests;
    }

    public function setEmergencyContactName($emergencyContactName) {
        $this->emergencyContactName = $emergencyContactName;
    }

    public function setEmergencyContactPhone($emergencyContactPhone) {
        $this->emergencyContactPhone = $emergencyContactPhone;
    }

    public function setProfileVisibility($profileVisibility) {
        $this->profileVisibility = $profileVisibility;
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'contactNumber' => $this->contactNumber,
            'isAdmin' => $this->isAdmin,
            'studentId' => $this->studentId,
            'dateOfBirth' => $this->dateOfBirth,
            'gender' => $this->gender,
            'yearLevel' => $this->yearLevel,
            'course' => $this->course,
            'major' => $this->major,
            'bio' => $this->bio,
            'hometown' => $this->hometown,
            'interests' => $this->interests,
            'emergencyContactName' => $this->emergencyContactName,
            'emergencyContactPhone' => $this->emergencyContactPhone,
            'profileVisibility' => $this->profileVisibility,
        ];
    }
}