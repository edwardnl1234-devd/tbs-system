<?php

namespace App\Http\Controllers;

use App\Http\Requests\Truck\StoreTruckRequest;
use App\Http\Requests\Truck\UpdateTruckRequest;
use App\Http\Resources\TruckResource;
use App\Models\Truck;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TruckController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Truck::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                    ->orWhere('driver_name', 'like', "%{$search}%");
            });
        }

        $trucks = $query->orderBy('plate_number')->paginate($request->per_page ?? 15);

        return $this->successPaginated($trucks);
    }

    public function store(StoreTruckRequest $request): JsonResponse
    {
        try {
            $truck = Truck::create($request->validated());

            return $this->created(new TruckResource($truck), 'Truck created successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create truck: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $truck = Truck::with(['queues' => function ($q) {
            $q->latest()->limit(10);
        }])->find($id);

        if (!$truck) {
            return $this->notFound('Truck not found');
        }

        return $this->success(new TruckResource($truck));
    }

    public function update(UpdateTruckRequest $request, int $id): JsonResponse
    {
        try {
            $truck = Truck::find($id);

            if (!$truck) {
                return $this->notFound('Truck not found');
            }

            $truck->update($request->validated());

            return $this->success(new TruckResource($truck), 'Truck updated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update truck: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $truck = Truck::find($id);

            if (!$truck) {
                return $this->notFound('Truck not found');
            }

            $truck->delete();

            return $this->success(null, 'Truck deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete truck: ' . $e->getMessage());
        }
    }

    public function search(Request $request): JsonResponse
    {
        $plateNumber = $request->get('plate_number');

        if (!$plateNumber) {
            return $this->error('Plate number is required', 400);
        }

        $trucks = Truck::where('plate_number', 'like', "%{$plateNumber}%")
            ->limit(10)
            ->get();

        return $this->success(TruckResource::collection($trucks));
    }
}
