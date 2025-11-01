<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtFilter implements FilterInterface
{
    /**
     * Run before the request is processed.
     * Checks for Authorization: Bearer <token> header and validates the JWT.
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        // Some servers put the header into the _SERVER superglobal
        if (empty($authHeader)) {
            $authHeader = $request->getServer('HTTP_AUTHORIZATION') ?: $request->getServer('REDIRECT_HTTP_AUTHORIZATION');
        }

        if (empty($authHeader)) {
            $res = Services::response();
            return $res->setStatusCode(401)->setJSON(['error' => 'Authorization header not found']);
        }

        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = trim(substr($authHeader, 7));
        } else {
            $token = trim($authHeader);
        }

        if (empty($token)) {
            $res = Services::response();
            return $res->setStatusCode(401)->setJSON(['error' => 'Bearer token not provided']);
        }

        try {
            $key = config('App')->JWTSecret ?? getenv('jwt.secret');
            if (empty($key)) {
                throw new \Exception('JWT secret not configured');
            }

            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            // Check cache for a revoked token fingerprint. If present, deny access.
            try {
                $cacheKey = 'revoked_jwt_' . sha1($token);
                $cache = Services::cache();
                if ($cache->get($cacheKey)) {
                    $res = Services::response();
                    return $res->setStatusCode(401)->setJSON(['error' => 'Token has been revoked']);
                }
            } catch (\Throwable $e) {
                // If cache isn't available or check fails, continue — cache is an optimization for revocation.
            }

            // Attach decoded token data to the request so controllers can access it.
            // In controllers: $this->request->user (or $this->request->getUser() if implemented).
            try {
                // Best-effort attach; many controllers access $this->request so adding a property is simple and effective.
                $request->user = (array) $decoded;
            } catch (\Throwable $e) {
                // If attaching fails, ignore — token is still valid and request proceeds.
            }

            return; // allowed

        } catch (\Exception $e) {
            $res = Services::response();
            return $res->setStatusCode(401)->setJSON(['error' => 'Invalid token', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Run after the request is processed.
     * Not used here.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do
    }
}
