<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->paginate($request->per_page ?? 15);

        return $this->successPaginated($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Auto-generate code if not provided
            if (empty($data['code'])) {
                $lastCustomer = Customer::where('code', 'like', 'CUST%')
                    ->orderBy('code', 'desc')
                    ->first();
                $nextNumber = $lastCustomer 
                    ? (int) substr($lastCustomer->code, 4) + 1 
                    : 1;
                $data['code'] = 'CUST' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
            
            // Handle is_active -> status mapping
            if (isset($data['is_active'])) {
                $data['status'] = $data['is_active'] ? 'active' : 'inactive';
                unset($data['is_active']);
            }
            
            // Handle frontend field mappings
            // Map 'company' to 'contact_person' if contact_person not provided
            if (!empty($data['company']) && empty($data['contact_person'])) {
                $data['contact_person'] = $data['company'];
            }
            
            // Remove fields not in database
            unset($data['company']);
            
            $customer = Customer::create($data);

            return $this->created(new CustomerResource($customer), 'Customer created successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create customer: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::with(['sales' => function ($q) {
            $q->latest()->limit(10);
        }])->find($id);

        if (!$customer) {
            return $this->notFound('Customer not found');
        }

        return $this->success(new CustomerResource($customer));
    }

    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return $this->notFound('Customer not found');
            }

            $data = $request->validated();
            
            // Handle is_active -> status mapping
            if (isset($data['is_active'])) {
                $data['status'] = $data['is_active'] ? 'active' : 'inactive';
                unset($data['is_active']);
            }
            
            // Handle frontend field mappings
            // Map 'company' to 'contact_person' if contact_person not provided
            if (!empty($data['company']) && empty($data['contact_person'])) {
                $data['contact_person'] = $data['company'];
            }
            
            // Remove fields not in database
            unset($data['company']);
            
            $customer->update($data);

            return $this->success(new CustomerResource($customer), 'Customer updated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update customer: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return $this->notFound('Customer not found');
            }

            $customer->delete();

            return $this->success(null, 'Customer deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete customer: ' . $e->getMessage());
        }
    }

    public function active(): JsonResponse
    {
        $customers = Customer::where('status', 'active')
            ->orderBy('name')
            ->get();

        return $this->success(CustomerResource::collection($customers));
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');

        if (!$query) {
            return $this->error('Search query is required', 400);
        }

        $customers = Customer::where('code', 'like', "%{$query}%")
            ->orWhere('name', 'like', "%{$query}%")
            ->orWhere('contact_person', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        return $this->success(CustomerResource::collection($customers));
    }
}
