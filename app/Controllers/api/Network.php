<?php

namespace App\Controllers\api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;

class Network extends ResourceController
{
    protected $format = 'json';
    protected $userModel;
    protected $request;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->request = request();
    }

    /**
     * GET /api/network/tree
     * GET /api/network/tree/{id}
     * Se id fornecido: retorna subárvore daquele usuário.
     * Se não: retorna todas as raízes com seus descendentes.
     */
    public function tree($id = null)
    {
        $db = \Config\Database::connect();
        $supportsCTE = $this->checkMysqlSupportsCTE($db);

        if ($supportsCTE) {
            $rows = $this->userModel->getHierarchyWithCTE($id ? (int)$id : null);
        } else {
            // fallback
            if ($id === null) {
                $rows = $this->userModel->getHierarchyFallback(null);
            } else {
                $rows = $this->userModel->getHierarchyFallback((int)$id);
            }
        }

        // Se o método retornou a lista plana (rows), transformar em árvore
        $tree = $this->buildTree($rows, $id);

        return $this->respond([
            'status' => 'success',
            'data' => $tree
        ], 200);
    }

    // Verifica suporte a CTE (MySQL 8+)
    protected function checkMysqlSupportsCTE($db)
    {
        try {
            $version = $db->getVersion();
            // Ex.: "8.0.27" -> pegar major
            $parts = explode('.', $version);
            $major = (int)$parts[0];
            return $major >= 8;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // Converte lista plana em árvore aninhada
    protected function buildTree(array $rows, $rootId = null)
    {
        // Normalizar rows (garantir índices)
        $nodes = [];
        foreach ($rows as $r) {
            // rows podem conter 'level' quando veio do CTE; manter apenas campos úteis
            $nodes[$r['id']] = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'email' => $r['email'],
                'role' => $r['role'],
                'campaing_id' => $r['campaign_id'],
                'phone' => $r['phone'],
                'invited_by' => isset($r['invited_by']) ? ($r['invited_by'] !== null ? (int)$r['invited_by'] : null) : null,
                'children' => []
            ];
        }

        // montar árvore
        $tree = [];
        foreach ($nodes as $id => &$node) {
            $parent = $node['invited_by'];
            if ($parent === null) {
                $tree[] = &$node; // raiz
            } else {
                if (isset($nodes[$parent])) {
                    $nodes[$parent]['children'][] = &$node;
                } else {
                    // parent não existe na lista (pode acontecer se usamos subárvore ou dados inconsistentes)
                    if ($rootId !== null && $id == $rootId) {
                        $tree[] = &$node;
                    }
                }
            }
        }
        unset($node);

        // Se pediu subárvore por ID, retornar apenas o nó raiz pedido (com children)
        if ($rootId !== null) {
            $rootId = (int)$rootId;
            if (isset($nodes[$rootId])) {
                return $nodes[$rootId];
            } else {
                return []; // root não encontrado
            }
        }

        return $tree;
    }

    public function getCampaignUser()
    {
        $data = $this->request->getJSON(true);
        if (!$data) {
            return $this->fail('Invalid JSON data', 400);
        }

        if (!isset($data['campaign_id']) || !isset($data['invited_by'])) {
            return $this->failValidationErrors([
                'message' => 'Both campaign_id and invited_by are required'
            ]);
        }

        $campaignId = (int)$data['campaign_id'];
        $invitedBy = (int)$data['invited_by'];

        // Busca todos os usuários da campanha
        $users = $this->userModel
            ->where('campaign_id', $campaignId)
            ->findAll();

        // Busca o usuário raiz (invited_by), mesmo que não pertença à campanha
        $rootUser = $this->userModel->find($invitedBy);

        if (!$users && !$rootUser) {
            return $this->respond([
                'status' => 'success',
                'data' => []
            ], 200);
        }

        // Garante que o usuário raiz esteja incluído
        if ($rootUser && !in_array($rootUser['id'], array_column($users, 'id'))) {
            $users[] = $rootUser;
        }

        // Remove dados sensíveis
        foreach ($users as &$user) {
            unset($user['password']);
        }

        // Montar a árvore (usando mesmo método da tree())
        $tree = $this->buildTreeCampaign($users, $invitedBy);

        return $this->respond([
            'status' => 'success',
            'data' => $tree
        ], 200);
    }

    /**
     * Monta uma árvore apenas para uma campanha específica.
     * Similar à buildTree(), mas parte de um nó raiz (invited_by)
     */
    protected function buildTreeCampaign(array $rows, $rootId = null)
    {
        $nodes = [];
        foreach ($rows as $r) {
            $nodes[$r['id']] = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'email' => $r['email'],
                'role' => $r['role'],
                'campaign_id' => $r['campaign_id'],
                'phone' => $r['phone'],
                'invited_by' => isset($r['invited_by']) ? ($r['invited_by'] !== null ? (int)$r['invited_by'] : null) : null,
                'children' => []
            ];
        }

        // Construir a hierarquia
        foreach ($nodes as $id => &$node) {
            $parent = $node['invited_by'];
            if ($parent !== null && isset($nodes[$parent])) {
                $nodes[$parent]['children'][] = &$node;
            }
        }
        unset($node);

        // Retornar a subárvore do nó pedido
        if ($rootId !== null && isset($nodes[$rootId])) {
            return $nodes[$rootId];
        }

        // Se não encontrar o nó raiz, retorna os nós de topo
        $tree = [];
        foreach ($nodes as $n) {
            if ($n['invited_by'] === null) {
                $tree[] = $n;
            }
        }

        return $tree;
    }
}
