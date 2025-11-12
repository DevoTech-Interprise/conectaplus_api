<?php

namespace App\Controllers\api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\EventsModel;

class EventsController extends ResourceController
{
    protected $modelName = EventsModel::class;
    protected $format    = 'json';
    protected $request;

    public function __construct()
    {
        $this->request = request();
    }

    public function index()
    {
        $events = $this->model->findAll();
        return $this->respond($events, 200);
    }

    public function show($id = null)
    {
        $event = $this->model->where('id', $id)->first();
        if (!$event) {
            return $this->failNotFound('Event not found');
        }
        return $this->respond($event, 200);
    }

    public function create()
    {
        helper(['form', 'url']);

        $data = $this->request->getJSON();

        if (!$this->model->validate($data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        try {

            $this->model->insert($data);
            $createdEvent = $this->model->where('id', $this->model->insertID())->first();
            return $this->respondCreated([
                'status' => 'success',
                'data' => $createdEvent
            ], 'Event created successfully');
        } catch (\Exception $e) {
            return $this->failServerError('Error creating event: ' . $e->getMessage());
        }
    }

    public function update($id = null)
    {
        helper(['form', 'url']);
        $data = $this->request->getJSON(true);

        if (!$this->model->where('id', $id)->first()) {
            return $this->failNotFound('Event not found');
        }

        if (!$this->model->validate($data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        try {

            $data = array_filter($data);
            $this->model->update($id, $data);
            $updatedEvent = $this->model->where('id', $id)->first();
            return $this->respond([
                'status' => 'success',
                'data' => $updatedEvent
            ], 200);

        } catch (\Exception $e) {
            return $this->failServerError('Error updating event: ' . $e->getMessage());
        }
    }

    public function delete($id = null)
    {
        if (!$this->model->where('id', $id)->first()) {
            return $this->failNotFound('Event not found');
        }

        try {
            $this->model->delete($id);
            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'Event deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('Error deleting event: ' . $e->getMessage());
        }
    }
}
