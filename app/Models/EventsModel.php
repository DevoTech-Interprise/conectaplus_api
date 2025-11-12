<?php 

namespace App\Models;
use CodeIgniter\Model;

class EventsModel extends Model
{
    protected $table = 'events';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'title',
        'description',
        'start_date',
        'end_date',
        'location',
        'event_type',
        'status',
        'campaign_id',
        'created_by',
        'created_at',
        'updated_at',
        'color',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validações
    protected $validationRules = [
        'title'       => 'required|min_length[3]|max_length[255]',
        'start_date'  => 'required|valid_date',
        'end_date'    => 'required|valid_date',
        'event_type'  => 'in_list[meeting,campaign,speech,visit,other]',
        'status'      => 'in_list[confirmed,pending,cancelled]',
        'campaign_id' => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'title' => [
            'required' => 'The title is required.',
        ],
        'start_date' => [
            'required' => 'The date start is required.',
        ],
        'end_date' => [
            'required' => 'The date end is required.',
        ],
    ];

    protected $skipValidation = false;

}

?>