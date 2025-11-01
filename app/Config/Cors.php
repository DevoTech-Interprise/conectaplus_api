<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    // public array $default = [
    //     'allowedOrigins'         => ['http://localhost'], // front-end confiável
    //     'supportsCredentials'    => true,
    //     'allowedHeaders'         => ['*'],
    //     'exposedHeaders'         => ['*'],
    //     'allowedMethods'         => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    //     'maxAge'                 => 7200,
    // ];

    // public array $default = [
    //     'allowedOrigins' => ['http://localhost:5173', 'https://ticonnecte.com.br'],
    //     'supportsCredentials' => true,
    //     'allowedHeaders' => ['*'],
    //     'exposedHeaders' => ['*'],
    //     'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    //     'maxAge' => 7200,
    // ];

    public array $default = [
        'allowedOrigins'         => ['*'],
        'supportsCredentials'    => false, // ← importante
        'allowedHeaders'         => ['*'],
        'exposedHeaders'         => ['*'],
        'allowedMethods'         => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'maxAge' => 7200,
    ];
}