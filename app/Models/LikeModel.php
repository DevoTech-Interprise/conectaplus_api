<?php

namespace App\Models;

use CodeIgniter\Model;

class LikeModel extends Model
{
    protected $table = 'likes';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'notice_id',
        'comment_id',
        'created_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $returnType    = 'array';

    protected $validationRules = [
        'user_id' => 'required|integer',
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User ID is required.'
        ]
    ];
}
