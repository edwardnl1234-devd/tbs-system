<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return $this->successPaginated($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => $request->status ?? 'active',
            ]);

            return $this->created(new UserResource($user), 'User created successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create user: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->notFound('User not found');
        }

        return $this->success(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->notFound('User not found');
            }

            $data = $request->validated();

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            return $this->success(new UserResource($user), 'User updated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update user: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->notFound('User not found');
            }

            // Prevent self-deletion
            if ($user->id === auth()->id()) {
                return $this->error('You cannot delete your own account', 400);
            }

            $user->delete();

            return $this->success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete user: ' . $e->getMessage());
        }
    }
}
