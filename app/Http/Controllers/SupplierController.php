<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->paginate($request->per_page ?? 15);

        return $this->successPaginated($suppliers);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Auto-generate code if not provided
            if (empty($data['code'])) {
                $prefix = strtoupper(substr($data['type'] ?? 'SUP', 0, 3));
                $lastSupplier = Supplier::where('code', 'like', $prefix . '%')
                    ->orderBy('code', 'desc')
                    ->first();
                $nextNumber = $lastSupplier 
                    ? (int) substr($lastSupplier->code, strlen($prefix)) + 1 
                    : 1;
                $data['code'] = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
            
            // Handle is_active -> status mapping
            if (isset($data['is_active'])) {
                $data['status'] = $data['is_active'] ? 'active' : 'inactive';
                unset($data['is_active']);
            }
            
            $supplier = Supplier::create($data);

            return $this->created(new SupplierResource($supplier), 'Supplier created successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create supplier: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return $this->notFound('Supplier not found');
        }

        return $this->success(new SupplierResource($supplier));
    }

    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return $this->notFound('Supplier not found');
            }

            $data = $request->validated();
            
            // Handle is_active -> status mapping
            if (isset($data['is_active'])) {
                $data['status'] = $data['is_active'] ? 'active' : 'inactive';
                unset($data['is_active']);
            }
            
            $supplier->update($data);

            return $this->success(new SupplierResource($supplier), 'Supplier updated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update supplier: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return $this->notFound('Supplier not found');
            }

            $supplier->delete();

            return $this->success(null, 'Supplier deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete supplier: ' . $e->getMessage());
        }
    }

    public function byType(string $type): JsonResponse
    {
        $suppliers = Supplier::where('type', $type)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return $this->success(SupplierResource::collection($suppliers));
    }
}
