<?php

namespace App\Repositories;

use App\Repositories\Contracts\PaymentTypeRepositoryInterface;
use App\Models\PaymentType;

class PaymentTypeRepository implements PaymentTypeRepositoryInterface
{
    public function getAll()
    {
        return PaymentType::all();
    }

    public function getById(string $id)
    {
        return PaymentType::findOrFail($id);
    }

    public function create(array $data)
    {
        return PaymentType::create($data);
    }

    public function update(string $id, array $data)
    {
        $paymentType = PaymentType::findOrFail($id);
        $paymentType->update($data);
        return $paymentType;
    }

    public function delete(string $id)
    {
        $paymentType = PaymentType::findOrFail($id);
        $paymentType->delete();
    }
}
