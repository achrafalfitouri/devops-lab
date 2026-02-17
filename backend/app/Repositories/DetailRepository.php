<?php
namespace App\Repositories;

use App\Repositories\Contracts\DetailRepositoryInterface;
use App\Models\Detail;

class DetailRepository implements DetailRepositoryInterface
{
    public function getAllDetails()
    {
        return Detail::all();
    }

    public function getDetailById($id)
    {
        return Detail::findOrFail($id);
    }

    public function createDetail(array $details)
    {
        return Detail::create($details);
    }

    public function updateDetail($id, array $details)
    {
        return Detail::whereId($id)->update($details);
    }

    public function deleteDetail($id)
    {
        return Detail::destroy($id);
    }
}