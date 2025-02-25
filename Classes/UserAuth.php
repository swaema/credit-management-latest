<?php
class UserAuth
{
    public static function register($data)
    {
        $user = new User(null, $data['name'], $data['email'], password_hash($data['password'], PASSWORD_BCRYPT), $data['role']);
        $user->save();
        return $user;
    }

    public static function login($email, $password)
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = User::findByEmail($email);
        if ($user && password_verify($password, $user->password)) {
            $_SESSION['user_status'] = $user->status;
            if ($user->status === "active") {
                // Set all session variables
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_role'] = $user->role;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_name'] = $user->name;
                $_SESSION['user_image'] = $user->image;
                $_SESSION['user_status'] = $user->status;
                return 1;
            } 
            else if($user->status === "suspend"){
                return 2;
            }
            else {
                return 0;
            }
        } 
        return -1;
    }

    public static function logout()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Only try to unset and destroy if session exists
        if (isset($_SESSION)) {
            // Unset all session variables
            $_SESSION = array();

            // If it's desired to kill the session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time()-3600, '/');
            }

            // Finally, destroy the session
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
        }

        // Redirect to login page
        header('Location: /login.php');
        exit();
    }

    public static function isAdminAuthenticated()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    public static function isBorrowerAuthenticated()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'borrower';
    }

    public static function isLenderAuthenticated()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'lender';
    }
}