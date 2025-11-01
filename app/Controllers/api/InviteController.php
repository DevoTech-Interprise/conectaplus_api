<?php

namespace App\Controllers\api;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use App\Models\CampaignModel;
use CodeIgniter\I18n\Time;

class InviteController extends ResourceController
{
    protected $format = 'json';
    protected $userModel;
    protected $campaignModel;
    protected $request;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->campaignModel = new CampaignModel();
        $this->request = request();
    }

    public function generate($userId)
    {
        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        // Gerar token único
        $token = bin2hex(random_bytes(16));

        // Salvar token no banco de dados
        $this->userModel->update($userId, ['invite_token' => $token]);

        $inviteLink = base_url("invite/$token");

        return $this->respond([
            'status' => 'success',
            'invite_token' => $inviteLink
        ], 200);
    }

    public function getModelCampaign()
    {
        $data = $this->request->getJSON(true);

        if (!isset($data['invite_token'])) {
            return $this->fail('Invite token is required', 400);
        }

        try{

            $inviter = $this->userModel->where('invite_token', $data['invite_token'])->first();

            if (!$inviter) {
                return $this->fail('Invalid invite token', 400);
            }

            if (!isset($inviter['campaign_id']) || !$inviter['campaign_id']) {
                return $this->fail('Inviter has no associated campaign', 400);
            }

            $campaign = $this->campaignModel->find($inviter['campaign_id']);

            if (!$campaign) {
                return $this->fail('Campaign not found', 404);
            }

            return $this->respond([
                'status' => 'success',
                'inviter' => $inviter,
                'campaign' => $campaign
            ], 200);

        } catch (\Exception $e) {
            return $this->failServerError('Error retrieving campaign: ' . $e->getMessage());
        }
    }

    public function accept()
    {
        $data = $this->request->getJSON(true);

        // Verifica se o token de convite foi informado
        if (!isset($data['invite_token'])) {
            return $this->fail('Invite token is required', 400);
        }

        // Busca o usuário que enviou o convite
        $inviter = $this->userModel->where('invite_token', $data['invite_token'])->first();

        if (!$inviter) {
            return $this->fail('Invalid invite token', 400);
        }

        // Regras de validação
        $rules = [
            'name'     => 'required|min_length[3]|max_length[50]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'phone'    => 'required|is_unique[users.phone]',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            // Cria o novo usuário herdando a campanha do convidador
            $newUser = [
                'name'        => $data['name'],
                'email'       => $data['email'],
                'password'    => $data['password'],
                'phone'       => $data['phone'],
                'gender'      => $data['gender'] ?? null,
                'country'      => $data['country'] ?? null,
                'state'       => $data['state'] ?? null,
                'city'        => $data['city'] ?? null,
                'neighborhood'=> $data['neighborhood'] ?? null,
                'invited_by'  => $inviter['id'],
                'campaign_id' => $inviter['campaign_id'] ?? null, // herda a campanha
                'invite_token' => bin2hex(random_bytes(16)) // gera token único pro novo usuário também
            ];

            $userId = $this->userModel->insert($newUser);

            return $this->respondCreated([
                'status'      => 'success',
                'message'     => 'User created successfully via invite',
                'user_id'     => $userId,
                'invited_by'  => $inviter['name'],
                'campaign_id' => $newUser['campaign_id']
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Error creating user: ' . $e->getMessage());
        }
    }
}
