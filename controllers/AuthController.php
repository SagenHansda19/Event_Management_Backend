<?php
require '../models/User.php';

class AuthController {


    private $userModel;

    public function __construct($pdo) {
        $this->userModel = new User($pdo);
    }

    public function login($username, $password) {
        $user = $this->userModel->findByUsername($username);
        
        if ($user && password_verify($password, $user['password'])) {
            return [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ];
        }
        return false;
    }
}
?>
