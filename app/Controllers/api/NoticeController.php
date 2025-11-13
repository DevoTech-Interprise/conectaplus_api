<?php 

namespace App\Controllers\api;
use CodeIgniter\RESTful\ResourceController;
use App\Models\NoticeModel;

class NoticeController extends ResourceController
{
    protected $modelName = NoticeModel::class;
    protected $format    = 'json';
    protected $request;

    public function __construct()
    {
        $this->request = request();
    }

    public function index()
    {
        $notices = $this->model->findAll();
        return $this->respond($notices, 200);
    }

    public function show($id = null)
    {
        $notice = $this->model->where('id', $id)->first();
        if (!$notice) {
            return $this->failNotFound('Notice not found');
        }
        return $this->respond($notice, 200);
    }

    public function create()
    {
        helper(['form', 'url']);

        // Use getPost because we expect multipart/form-data when uploading an image
        $data = $this->request->getPost();

        // Handle image upload (input name: 'image')
        $imageFile = $this->request->getFile('image');
        $imageUrl = null;
        if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
            $newName = $imageFile->getRandomName();
            $uploadPath = FCPATH . 'upload/notices/images/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            $imageFile->move($uploadPath, $newName);
            $baseUrl = base_url('upload/notices/images/');
            $imageUrl = $baseUrl . $newName;
        }

        if ($imageUrl !== null) {
            $data['image'] = $imageUrl; // store image URL in DB
        }

        // Remove empty values so we don't overwrite existing fields with null/empty
        $data = array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });

        try {
            $this->model->insert($data);
            $createdNotice = $this->model->where('id', $this->model->insertID())->first();
            return $this->respondCreated([
                'status' => 'success',
                'data' => $createdNotice
            ], 'Notice created successfully');
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->failServerError('Error creating notice: ' . $e->getMessage());
        }
    }

    public function update($id = null)
    {
        helper(['form', 'url']);

        $notice = $this->model->find($id);
        if (!$notice) {
            return $this->failNotFound('Notice not found');
        }

        // Use getPost for multipart/form-data updates with optional image
        $data = $this->request->getPost();

        // Validate with model rules (only for provided fields)
        if (!$this->model->validate($data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        // Handle image upload
        $imageFile = $this->request->getFile('image');
        if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
            $uploadPath = FCPATH . 'upload/notices/images/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            $newName = $imageFile->getRandomName();
            $imageFile->move($uploadPath, $newName);
            $baseUrl = base_url('upload/notices/images/');
            $newImageUrl = $baseUrl . $newName;

            // Delete old image if exists
            if (!empty($notice['image'])) {
                $oldPath = FCPATH . str_replace(base_url(), '', $notice['image']);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $data['image'] = $newImageUrl;
        }

        // Remove empty values to avoid overwriting with null/empty
        $data = array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });

        try {
            $this->model->update($id, $data);
            $updatedNotice = $this->model->where('id', $id)->first();
            return $this->respond([
                'status' => 'success',
                'data' => $updatedNotice
            ], 200);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->failServerError('Error updating notice: ' . $e->getMessage());
        }
    }

    public function delete($id = null)
    {
        if (!$this->model->find($id)) {
            return $this->failNotFound('Notice not found');
        }

        try {
            $this->model->delete($id);
            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'Notice deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Error deleting notice: ' . $e->getMessage());
        }
    }
}

?>