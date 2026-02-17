<?php
namespace App\Http\Controllers;

use App\Repositories\Contracts\DetailRepositoryInterface;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;


class DetailController extends Controller
{
    private DetailRepositoryInterface $detailRepository;

    public function __construct(DetailRepositoryInterface $detailRepository)
    {
        $this->detailRepository = $detailRepository;
    }

    public function index()
    {
        return response()->json([
            'data' => $this->detailRepository->getAllDetails()
        ]);
    }

    public function show($id)
    {

        return response()->json([
            'data' => $this->detailRepository->getDetailById($id)
        ]);
    }

    public function store(Request $request)
    {
        $details = $request->validate([
            'name' => 'required|string|max:255',
            'detail' => 'nullable|string',
        ]);

        return response()->json([
            'data' => $this->detailRepository->createDetail($details)
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $details = $request->validate([
            'name' => 'sometimes|string|max:255',
            'detail' => 'nullable|string',
        ]);

        return response()->json([
            'data' => $this->detailRepository->updateDetail($id, $details)
        ]);
    }

    public function destroy($id)
    {
        $this->detailRepository->deleteDetail($id);
        return response()->json(null, 204);
    }
}