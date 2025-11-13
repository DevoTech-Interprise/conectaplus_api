<?php

namespace App\Controllers\api;

use App\Models\LikeModel;
use App\Models\CommentModel;
use CodeIgniter\RESTful\ResourceController;

class LikeController extends ResourceController
{
    protected $modelName = LikeModel::class;
    protected $format    = 'json';
    protected $request;

    public function __construct()
    {
        $this->request = request();
    }

    public function toggle()
    {
        $data = $this->request->getJSON(true);
        $user_id = $data['user_id'];
        $notice_id = $data['notice_id'] ?? null;
        $comment_id = $data['comment_id'] ?? null;

        $like = $this->model
            ->where('user_id', $user_id)
            ->groupStart()
                ->where('notice_id', $notice_id)
                ->orWhere('comment_id', $comment_id)
            ->groupEnd()
            ->first();

        if ($like) {
            // Remove like
            $this->model->delete($like['id']);
            return $this->respond(['liked' => false]);
        }

        // Add like
        $this->model->insert($data);

        // Optionally update like count in comments
        if ($comment_id) {
            $commentModel = new CommentModel();
            $commentModel->where('id', $comment_id)
                         ->set('likes_count', 'likes_count + 1', false)
                         ->update();
        }

        return $this->respond(['liked' => true]);
    }

    public function likesByNotice($notice_id)
    {
        // All likes related to this notice (including likes on comments)
        $likes = $this->model->where('notice_id', $notice_id)->findAll();

        // Count likes that are directly on the notice (comment_id IS NULL)
        $builder = $this->model->builder();
        $noticeLikesCount = $builder
            ->where('notice_id', $notice_id)
            ->where('comment_id', null)
            ->countAllResults();

        // Count likes per comment under this notice
        $builder = $this->model->builder();
        $commentsLikes = $builder
            ->select('comment_id, COUNT(*) as likes_count')
            ->where('notice_id', $notice_id)
            ->where('comment_id IS NOT NULL', null, false)
            ->groupBy('comment_id')
            ->get()
            ->getResultArray();

        // Total likes related to the notice (including comment likes)
        $totalLikesCount = count($likes);

        return $this->respond([
            'status' => 'success',
            'notice_likes_count' => (int)$noticeLikesCount,
            'total_likes_count' => (int)$totalLikesCount,
            'comments_likes' => $commentsLikes,
            'likes' => $likes
        ]);
    }
}
