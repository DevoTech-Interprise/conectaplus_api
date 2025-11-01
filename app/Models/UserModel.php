<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'email', 'password', 'phone', 'gender', 'country', 'state', 'city', 'neighborhood', 'role', 'invited_by', 'campaign_id', 'invite_token'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $returnType = 'array';
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    public function getHierarchyWithCTE(int $rootId = null)
    {
        $db = $this->db;
        $builder = $db->table($this->table);

        if ($rootId !== null) {
            $sql = "
            WITH RECURSIVE cte AS (
              SELECT id, name, email, role, invited_by, 0 AS level
              FROM {$this->table}
              WHERE id = ?
              UNION ALL
              SELECT u.id, u.name, u.email, u.role, u.invited_by, cte.level + 1
              FROM {$this->table} u
              JOIN cte ON u.invited_by = cte.id
            )
            SELECT * FROM cte ORDER BY level, id;
            ";
            $query = $db->query($sql, [$rootId]);
        } else {
            // pegar todas as raízes (invited_by IS NULL)
            $sql = "
            WITH RECURSIVE cte AS (
              SELECT id, name, email, role, invited_by, 0 AS level
              FROM {$this->table}
              WHERE invited_by IS NULL
              UNION ALL
              SELECT u.id, u.name, u.email, u.role, u.invited_by, cte.level + 1
              FROM {$this->table} u
              JOIN cte ON u.invited_by = cte.id
            )
            SELECT * FROM cte ORDER BY level, id;
            ";
            $query = $db->query($sql);
        }

        return $query->getResultArray();
    }

    public function getHierarchyFallback(int $rootId = null)
    {
        // pega todos os usuários (pode limitar campos)
        $all = $this->select('id, name, email, role, invited_by')->findAll();

        if ($rootId === null) {
            return $all; // O frontend/pHP fará a montagem da árvore usando invited_by NULL como raízes
        }

        // se pediu subárvore, vamos filtrar os que não são descendentes do rootId
        // Construir um array de lookup ID => children (iterativo BFS)
        $byId = [];
        foreach ($all as $u) $byId[$u['id']] = $u + ['children' => []];

        // montar filhos
        foreach ($byId as $id => $user) {
            $parent = $user['invited_by'];
            if ($parent !== null && isset($byId[$parent])) {
                $byId[$parent]['children'][] = &$byId[$id];
            }
        }

        // Agora pegar o nó raíz pedido e coletar recursivamente seus ids (BFS)
        if (!isset($byId[$rootId])) {
            return []; // root inexistente
        }

        // coletar todos descendentes do rootId (inclui root)
        $result = [];
        $queue = [$byId[$rootId]];
        while (!empty($queue)) {
            $node = array_shift($queue);
            // remover children para que retorno seja plano (opcional)
            $copy = $node;
            $children = $copy['children'];
            unset($copy['children']);
            $result[] = $copy;
            foreach ($children as $c) $queue[] = $c;
        }

        return $result;
    }
}
