<?php

namespace App\Controllers\api;

use App\Models\CommentModel;
use CodeIgniter\RESTful\ResourceController;

class CommentController extends ResourceController
{
    protected $modelName = CommentModel::class;
    protected $format    = 'json';
    protected $request;

    public function __construct()
    {
        $this->request = request();
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        if (!$this->model->insert($data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respondCreated(['message' => 'Comment added successfully']);
    }

    public function byNotice($notice_id)
    {
        // Get only parent comments (where parent_id is null)
        $parentComments = $this->model
            ->where('notice_id', $notice_id)
            ->where('parent_id', null)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        // Build structure with nested replies
        $formattedComments = [];
        foreach ($parentComments as $parent) {
            // Get all replies for this parent comment
            $replies = $this->model
                ->where('parent_id', $parent['id'])
                ->orderBy('created_at', 'ASC')
                ->findAll();

            $formattedComments[] = [
                'comment' => $parent,
                'reply' => $replies
            ];
        }

        return $this->respond($formattedComments);
    }

    public function replies($parent_id)
    {
        $replies = $this->model
            ->where('parent_id', $parent_id)
            ->findAll();

        return $this->respond($replies);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);

        if (!$this->model->update($id, $data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond(['message' => 'Comment updated successfully']);
    }

    public function delete($id = null)
    {
        if (!$this->model->find($id)) {
            return $this->failNotFound('Comment not found');
        }

        $this->model->delete($id);
        return $this->respondDeleted(['message' => 'Comment deleted successfully']);
    }
}
