<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'staff',
                'status' => 'active',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->created([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'User registered successfully');
        } catch (\Exception $e) {
            return $this->serverError('Registration failed: ' . $e->getMessage());
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                return $this->unauthorized('Invalid credentials');
            }

            $user = User::where('email', $request->email)->firstOrFail();

            if ($user->status !== 'active') {
                return $this->forbidden('Your account is inactive. Please contact administrator.');
            }

            // Revoke previous tokens
            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->success([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->serverError('Login failed: ' . $e->getMessage());
        }
    }

    public function logout(): JsonResponse
    {
        try {
            auth()->user()->currentAccessToken()->delete();

            return $this->success(null, 'Logged out successfully');
        } catch (\Exception $e) {
            return $this->serverError('Logout failed: ' . $e->getMessage());
        }
    }

    public function user(): JsonResponse
    {
        return $this->success(new UserResource(auth()->user()));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $user->update($request->validated());

            return $this->success(new UserResource($user), 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Profile update failed: ' . $e->getMessage());
        }
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return $this->success(null, 'Password changed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Password change failed: ' . $e->getMessage());
        }
    }
}
