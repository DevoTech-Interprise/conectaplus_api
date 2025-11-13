<?php 

namespace App\Models;
use CodeIgniter\Model;

class CommentModel extends Model
{
    protected $table = 'comments';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'notice_id',
        'user_id',
        'parent_id',
        'content',
        'likes_count',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $returnType = 'array';

    protected $validationRules = [
        'notice_id' => 'required|integer',
        'user_id'   => 'required|integer',
        'content'   => 'required|min_length[2]'
    ];

    protected $validationMessages = [
        'notice_id' => [
            'required' => 'The notice ID is required.'
        ],
        'user_id' => [
            'required' => 'The user ID is required.'
        ],
        'content' => [
            'required' => 'The comment content is required.',
            'min_length' => 'The comment must have at least 2 characters.'
        ]
    ];
}

?>