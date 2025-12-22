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

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
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
            $customer = Customer::create($request->validated());

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

            $customer->update($request->validated());

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
