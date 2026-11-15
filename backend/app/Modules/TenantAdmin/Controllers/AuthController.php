<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\TenantAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected TenantAuthService $authService;

    public function __construct(TenantAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login($validated['email'], $validated['password']);

        return response()->json([
            'success' => true,
            'message' => 'Signed in successfully to Tenant Admin Control Center.',
            'data' => $result,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->authService->getMe($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Successfully signed out.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        $this->authService->changePassword($request->user(), $validated['current_password'], $validated['new_password']);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }
}
