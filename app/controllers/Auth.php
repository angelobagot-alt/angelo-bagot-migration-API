<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Auth extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->library('api');
        $this->call->database();
    }

    public function login()
    {
        $this->api->require_method('POST');

        $payload = $this->api->body();
        $identifier = trim((string) ($payload['identifier'] ?? $payload['username'] ?? $payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($identifier === '' || $password === '') {
            $this->api->respond_error('Email or username and password are required.', 400);
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $stmt = $this->db->raw(
                'SELECT * FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1',
                [$identifier]
            );
        } else {
            $stmt = $this->db->raw(
                'SELECT * FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1',
                [$identifier]
            );
        }
        $user = $stmt ? $stmt->fetch(PDO::FETCH_OBJ) : false;
        // The escaped candidate keeps compatibility with accounts registered
        // before password fields were excluded from API input sanitization.
        $legacyPassword = htmlspecialchars(trim($password), ENT_QUOTES, 'UTF-8');
        $passwordMatches = $user && (
            password_verify($password, $user->password)
            || password_verify($legacyPassword, $user->password)
        );
        if (!$user || empty($user->is_active) || !$passwordMatches) {
            $this->api->respond_error('Invalid username or password.', 401);
        }

        $this->respond_with_tokens($user, 'Login successful.', 200);
    }

    public function register()
    {
        $this->api->require_method('POST');
        $payload = $this->api->body();
        $username = trim((string) ($payload['username'] ?? ''));
        // The current sign-up form collects a username rather than a full name.
        // Keep legacy required name columns populated until the form collects them.
        $firstname = trim((string) ($payload['firstname'] ?? $username));
        $lastname = trim((string) ($payload['lastname'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if (strlen($username) < 3 || strlen($username) > 100) {
            $this->api->respond_error('Username must be between 3 and 100 characters.', 400);
        }
        if (strlen($firstname) > 100 || strlen($lastname) > 100) {
            $this->api->respond_error('First and last name must be at most 100 characters.', 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $this->api->respond_error('Enter a valid email address.', 400);
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $this->api->respond_error('Password must be between 8 and 72 characters.', 400);
        }

        $existing = $this->db->raw(
            'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$username, $email]
        );
        if ($existing && $existing->fetch(PDO::FETCH_ASSOC)) {
            $this->api->respond_error('That username or email is already registered.', 409);
        }

        $userId = $this->db->table('users')->insert([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $user = $this->db->table('users')->where('id', $userId)->limit(1)->get();
        if (!$user) {
            $this->api->respond_error('Account created, but sign-in could not be started. Please sign in.', 500);
        }

        $this->api->respond([
            'message' => 'Account created successfully. Please sign in.',
            'user' => [
                'id' => (int) $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }

    private function respond_with_tokens($user, $message, $status)
    {
        $tokens = $this->api->issue_tokens([
            'id' => (int) $user->id,
            'role' => $user->role,
            'scopes' => ['read', 'write'],
        ]);

        $this->api->respond([
            'message' => $message,
            'token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'user' => [
                'id' => (int) $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], $status);
    }

    public function logout()
    {
        $this->api->require_method('POST');
        $this->call->database();
        $token = $this->api->get_bearer_token();
        if ($token) {
            $payload = $this->api->validate_jwt($token, 'access');
            if ($payload) {
                $refreshToken = $this->api->body()['refresh_token'] ?? '';
                if ($refreshToken !== '') {
                    $this->api->revoke_refresh_token($refreshToken);
                }
            }
        }
        $this->api->respond([
            'message' => 'Logout successful.'
        ], 200);
    }
}
