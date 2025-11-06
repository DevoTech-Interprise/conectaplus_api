<?php 

namespace App\Models;
use CodeIgniter\Model;

class CampaignModel extends Model
{
    protected $table = 'campaigns';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name',	'description',	'logo',	'color_primary', 'operator', 'color_secondary',	'created_by'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}

?>