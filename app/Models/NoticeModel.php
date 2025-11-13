<?php

namespace App\Models;

use CodeIgniter\Model;

class NoticeModel extends Model
{
    protected $table = 'notices';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'title',
        'preview',
        'image',
        'content',
        'campaign_id',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $returnType = 'array';

    protected $validationRules = [
        'title'       => 'required|min_length[3]|max_length[255]',
        'preview'     => 'required',
        'image'       => 'permit_empty|max_length[255]',
        'content'     => 'required|max_length[255]',
        'campaign_id' => 'required|integer',
        'created_by'  => 'required|integer',
    ];

    protected $validationMessages = [
        'title' => [
            'required'   => 'The title field is required.',
            'min_length' => 'The title must be at least 3 characters long.',
            'max_length' => 'The title cannot exceed 255 characters.'
        ],
        'preview' => [
            'required' => 'The preview field is required.'
        ],
        'image' => [
            'max_length' => 'The image field cannot exceed 255 characters.'
        ],
        'content' => [
            'required'   => 'The content field is required.',
            'max_length' => 'The content cannot exceed 255 characters.'
        ],
        'campaign_id' => [
            'required' => 'The campaign ID is required.',
            'integer'  => 'The campaign ID must be an integer.'
        ],
        'created_by' => [
            'required' => 'The creator ID is required.',
            'integer'  => 'The creator ID must be an integer.'
        ],
    ];

    protected $skipValidation = false;
}
