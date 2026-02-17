<?php

namespace App\Repositories\Contracts;

interface DetailRepositoryInterface
{
    public function getAllDetails();
    public function getDetailById($id);
    public function createDetail(array $details);
    public function updateDetail($id, array $details);
    public function deleteDetail($id);
}