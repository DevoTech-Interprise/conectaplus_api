<?php 

namespace App\Models;
use CodeIgniter\Model;

class NoticeModel extends Model
{
    protected $table = 'notices';
    protected $primaryKey = 'id';
    protected $allowedFields = ['title', 'message', 'user_id', 'created_at', 'updated_at'];
}

?>