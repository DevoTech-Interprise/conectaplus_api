<?php

namespace App\Controllers\api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CampaignModel;

class CampaignController extends ResourceController
{
    protected $modelName = CampaignModel::class;
    protected $format    = 'json';
    protected $request;

    public function __construct()
    {
        $this->request = request();
    }



    public function index()
    {
        $campaigns = $this->model->findAll();
        return $this->respond($campaigns, 200);
    }

    public function show($id = null)
    {
        $campaign = $this->model->where('created_by', $id)->first();
        if (!$campaign) {
            return $this->failNotFound('Campaign not found');
        }
        return $this->respond($campaign, 200);
    }

    public function create()
    {
        helper(['form', 'url']);

        $data = $this->request->getPost(); // Usa getPost() pq tem upload (multipart/form-data)

        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'description' => 'permit_empty|max_length[500]',
            'color_primary' => 'permit_empty|regex_match[/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/]',
            'color_secondary' => 'permit_empty|regex_match[/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/]',
            'created_by' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // ====== Upload da imagem ======
        $logoFile = $this->request->getFile('logo'); // nome do input deve ser 'logo'
        $logoUrl = null;

        if ($logoFile && $logoFile->isValid() && !$logoFile->hasMoved()) {
            $newName = $logoFile->getRandomName();
            $uploadPath = FCPATH . 'upload/campaigns/logos/';

            // Cria diretório caso não exista
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Move o arquivo
            $logoFile->move($uploadPath, $newName);

            // Monta a URL (ajuste o domínio conforme o seu ambiente)
            $baseUrl = base_url('upload/campaigns/logos/');
            $logoUrl = $baseUrl . $newName;
        }

        $data['logo'] = $logoUrl; // apenas URL no banco

        try {
            $campaignId = $this->model->insert($data);
            $campaign = $this->model->find($campaignId);
            return $this->respondCreated($campaign);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->failServerError('Erro ao criar campanha.');
        }
    }

    public function update($id = null)
    {
        helper(['form', 'url']);

        $campaign = $this->model->find($id);
        if (!$campaign) {
            return $this->failNotFound('Campaign not found');
        }

        // Para upload, usamos getPost()
        $data = $this->request->getPost();

        $rules = [
            'name' => 'permit_empty|min_length[3]|max_length[100]',
            'description' => 'permit_empty|max_length[500]',
            'color_primary' => 'permit_empty|regex_match[/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/]',
            'color_secondary' => 'permit_empty|regex_match[/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/]',
            'created_by' => 'permit_empty|integer',
        ];

        // Validação só dos campos enviados
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // ============ Upload de nova logo (opcional) ============
        $logoFile = $this->request->getFile('logo');
        if ($logoFile && $logoFile->isValid() && !$logoFile->hasMoved()) {

            $uploadPath = FCPATH . 'upload/campaigns/logos/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $newName = $logoFile->getRandomName();
            $logoFile->move($uploadPath, $newName);

            // Monta URL da nova imagem
            $baseUrl = base_url('upload/campaigns/logos/');
            $newLogoUrl = $baseUrl . $newName;

            // Deleta logo antiga se existir
            if (!empty($campaign['logo'])) {
                $oldPath = FCPATH . str_replace(base_url(), '', $campaign['logo']);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $data['logo'] = $newLogoUrl;
        }

        // ============ Remove campos vazios (para não sobrescrever com null) ============
        $data = array_filter($data, fn($value) => $value !== null && $value !== '');

        try {
            $this->model->update($id, $data);
            $updatedCampaign = $this->model->find($id);
            return $this->respond($updatedCampaign, 200);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->failServerError('An error occurred while updating the campaign');
        }
    }


    public function delete($id = null)
    {
        $campaign = $this->model->find($id);
        if (!$campaign) {
            return $this->failNotFound('Campaign not found');
        }

        try {
            $this->model->delete($id);
            return $this->respondDeleted(['message' => 'Campaign deleted successfully']);
        } catch (\Exception $e) {
            return $this->failServerError('An error occurred while deleting the campaign');
        }
    }
}
