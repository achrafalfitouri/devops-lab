<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\PaymentLogRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PaymentLogController extends Controller
{
    protected $paymentRepository;
    protected $paymentLogRepository;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        PaymentLogRepositoryInterface $paymentLogRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->paymentLogRepository = $paymentLogRepository;
    }

   
    public function updatePayment(Request $request, $id)
    {
        $updatedPayment = $this->paymentRepository->update($id, $request->all());
        return response()->json(['error' => 'payment updated successfully', 'payment' => $updatedPayment]);
        }
    
    
    
    public function deletePayment($id)
    {
        $deleted = $this->paymentRepository->delete($id);
        if ($deleted) {
            return response()->json(['error' => 'payment deleted successfully']);
        }
        return response()->json(['error' => 'payment not found'], 404);
    }
    
}
