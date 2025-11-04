<?php namespace App\Controllers\api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;

class Network extends ResourceController
{
    protected $format = 'json';
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
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
}
