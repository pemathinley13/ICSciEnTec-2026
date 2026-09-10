<?php

namespace App\Auth;

use App\Models\User;

final class Rbac
{
    /**
     * Call at the top of every protected page. Redirects to login if no
     * session, or shows 403 if logged in but missing every required role.
     * Pass an empty array to require only "logged in", no specific role.
     *
     * @param string[] $anyOfRoles
     */
    public static function requireRole(array $anyOfRoles = []): array
    {
        if (!Session::isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? null;
            header('Location: /auth/login.php');
            exit;
        }

        $userId = Session::userId();
        $user = User::findById($userId);
        if (!$user) {
            // Account deleted/deactivated mid-session.
            Session::logout();
            header('Location: /auth/login.php');
            exit;
        }

        if (!empty($anyOfRoles)) {
            $userRoles = User::rolesFor($userId);
            $intersection = array_intersect($anyOfRoles, $userRoles);
            if (empty($intersection)) {
                http_response_code(403);
                require dirname(__DIR__, 2) . '/templates/403.php';
                exit;
            }
        }

        return $user;
    }
}
