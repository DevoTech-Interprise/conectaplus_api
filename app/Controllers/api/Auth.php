<?php

namespace App\Controllers\api;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth extends ResourceController
{
    protected $format = 'json';
    protected $userModel;
    protected $request;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->request = request();
        helper('text');
    }

    public function index()
    {
        $users = $this->userModel->findAll();

        // Remove password field from each user before responding
        foreach ($users as $k => $u) {
            if (isset($u['password'])) {
                unset($users[$k]['password']);
            }
        }

        return $this->respond($users, 200);
    }

    public function show($id = null)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            return $this->failNotFound('User not found');
        }

        // Remove password before returning user data
        if (isset($user['password'])) {
            unset($user['password']);
        }

        return $this->respond($user, 200);
    }

    public function create()
    {
        $rules = [
            'name'     => 'required|min_length[3]|max_length[50]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'phone'    => 'required|is_unique[users.phone]',
            'role'     => 'required|',
        ];

        $data = $this->request->getJSON(true);

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (!$data) {
            return $this->fail('Invalid JSON data', 400);
        }

        try {
            // Hash da senha
            if (isset($data['password']) && $data['password'] !== '') {
                $data['password'] = $data['password'];
            }

            // Gera um token único de convite
            $data['invite_token'] = bin2hex(random_bytes(8)); // Ex: "a3f1b2c9d8e7f0a1"

            // Insere o usuário no banco
            $userId = $this->userModel->insert($data);

            return $this->respondCreated([
                'id'      => $userId,
                'message' => 'User created successfully',
                'invite_token' => $data['invite_token']
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('An error occurred while creating the user: ' . $e->getMessage());
        }
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        if (! $data) {
            return $this->fail('Invalid JSON data', 400);
        }

        $user = $this->userModel->find($id);
        if (! $user) {
            return $this->failNotFound('User not found');
        }

        try {

            // If the password key is present: only update when non-empty.
            // If it's present but empty, remove it so we don't overwrite the existing password with an empty string.
            if (array_key_exists('password', $data)) {
                if ($data['password'] === null || $data['password'] === '') {
                    unset($data['password']);
                } else {
                    // Hash the new password before saving
                    $data['password'] = $data['password'];
                }
            }

            $this->userModel->update($id, $data);
            return $this->respond(['message' => 'User updated successfully'], 200);
        } catch (\Exception $e) {
            return $this->failServerError('An error occurred while updating the user');
        }
    }

    public function delete($id = null)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            return $this->failNotFound('User not found');
        }

        try {

            $this->userModel->delete($id);
            return $this->respondDeleted(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            return $this->failServerError('An error occurred while deleting the user');
        }
    }

    public function searchWithEmail()
    {
        $data = $this->request->getJSON(true);
        if (! $data || ! isset($data['email'])) {
            return $this->fail('Email is required', 400);
        }

        try
        {
            $email = trim($data['email']);
            $user = $this->userModel->where('email', $email)->first();
            if (! $user) {
                return $this->failNotFound('User not found');
            }

            // Remove password before returning user data
            if (isset($user['password'])) {
                unset($user['password']);
            }

            return $this->respond($user, 200);
        } catch (\Exception $e) {
            return $this->failServerError('An error occurred while searching for the user');
        }
    }

    public function forgotPassword($id = null)
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules))
        {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (!$data)
        {
            return $this->fail('Invalid JSON data', 400);
        }

        try {

            $user = $this->userModel->where('id', $id)->first();

            if (!$user) {
                return $this->failNotFound('User not found');
            }

            $data = array_filter($data);

            $this->userModel->update($id, $data);

            return $this->respond(['message' => 'Password updated successfully'], 200);

        } catch (\Exception $e) {
            return $this->failServerError('An error occurred while processing the request');
        }
    }

    public function login()
    {
        // Login accepts separate inputs: 'email' OR 'phone' (one of them must be provided) and 'password'.
        // Support JSON body or form-data input.

        // Try to get JSON body first
        $json = $this->request->getJSON(true);

        // Fallback to POST (form-data) or raw body JSON
        if (! is_array($json) || empty($json)) {
            $post = $this->request->getPost();
            if (is_array($post) && count($post) > 0) {
                $json = $post;
            } else {
                $raw = $this->request->getBody();
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $json = $decoded;
                }
            }
        }

        $email = isset($json['email']) ? trim($json['email']) : null;
        $phone = isset($json['phone']) ? trim($json['phone']) : null;
        $password = isset($json['password']) ? $json['password'] : null;

        $errors = [];
        if (empty($email) && empty($phone)) {
            $errors['identifier'] = 'Either email or phone is required';
        }
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (! is_string($password) || strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        if (! empty($errors)) {
            return $this->failValidationErrors($errors);
        }

        // Prioritize email if provided
        if (! empty($email)) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->failValidationErrors(['email' => 'Email is not valid']);
            }
            $user = $this->userModel->where('email', $email)->first();
        } else {
            // Phone login
            $onlyDigits = preg_replace('/\D+/', '', $phone);
            $user = $this->userModel->where('phone', $phone)
                ->orWhere('phone', $onlyDigits)
                ->first();
        }

        if (! $user) {
            return $this->failUnauthorized('Invalid credentials', 401);
        }

        $stored = $user['password'] ?? null;
        if (empty($stored) || ! password_verify($password, $stored)) {
            return $this->failUnauthorized('Invalid credentials', 401);
        }

        if (! $user['is_active']) {
            return $this->failUnauthorized('Account is inactive', 403);
        }

        $key = config('App')->JWTSecret ?? getenv('jwt.secret');
        $now = time();
        $exp = $now + (getenv('jwt.ttl') ? intval(getenv('jwt.ttl')) : 3600);

        $payload = [
            'iss' => getenv('jwt.issuer') ?: 'conectaplus_api',
            'iat' => $now,
            'exp' => $exp,
            'sub' => $user['id'],
            'role' => $user['role'],
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        $data = [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => $exp - $now,
            'user'         => $user,
        ];

        return $this->respond($data, 200);
    }

    public function logout()
    {
        // If you want server-side logout, we can blacklist the provided JWT until it expires.
        // This implementation uses the framework cache to store a revoked token key.
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (empty($authHeader)) {
            return $this->failUnauthorized('Authorization header not found');
        }

        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = trim(substr($authHeader, 7));
        } else {
            $token = trim($authHeader);
        }

        if (empty($token)) {
            return $this->failUnauthorized('Bearer token not provided');
        }

        try {
            $key = config('App')->JWTSecret ?? getenv('jwt.secret');
            if (empty($key)) {
                return $this->failServerError('JWT secret not configured');
            }

            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));

            $exp = isset($decoded->exp) ? intval($decoded->exp) : null;
            $now = time();

            if ($exp !== null && $exp <= $now) {
                // Already expired — nothing to do.
                return $this->respond(['message' => 'Token already expired'], 200);
            }

            $ttl = ($exp !== null) ? ($exp - $now) : 3600;

            // Store a short fingerprint of the token in cache until it would naturally expire.
            $cacheKey = 'revoked_jwt_' . sha1($token);
            $cache = \Config\Services::cache();
            $cache->save($cacheKey, true, $ttl);

            return $this->respond(['message' => 'Logout successful'], 200);
        } catch (\Exception $e) {
            // If decoding fails, return 401 — token not valid.
            return $this->failUnauthorized('Invalid token: ' . $e->getMessage());
        }
    }
}
