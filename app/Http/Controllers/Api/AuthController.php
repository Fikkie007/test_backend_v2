<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        if (User::where('username', $request->username)->exists()) {
            return response()->json(['success' => false, 'code' => 409, 'message' => 'Username already exists'], 409);
        }

        try {
            $user = User::create(['username' => $request->username, 'name' => $request->username, 'password' => $request->password]);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                return response()->json(['success' => false, 'code' => 409, 'message' => 'Username already exists'], 409);
            }

            throw $exception;
        }
        return response()->json(['success' => true, 'code' => 201, 'message' => 'User registered successfully', 'data' => new UserResource($user)], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('username', $request->username)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'code' => 401, 'message' => 'Invalid credentials'], 401);
        }

        $access = $user->createToken('access', ['*'], now()->addMinutes(60))->plainTextToken;
        $refresh = Str::random(80);
        RefreshToken::create(['user_id' => $user->id, 'token_hash' => hash('sha256', $refresh), 'expires_at' => now()->addDays(30)]);

        return response()->json(['success' => true, 'code' => 200, 'message' => 'Login successful', 'data' => ['user' => new UserResource($user), 'access_token' => $access, 'refresh_token' => $refresh, 'token_type' => 'Bearer', 'expires_in' => 3600]]);
    }

    public function refresh(): JsonResponse
    {
        $tokenHash = hash('sha256', (string) request()->input('refresh_token'));

        return DB::transaction(function () use ($tokenHash) {
            $token = RefreshToken::where('token_hash', $tokenHash)->whereNull('revoked_at')->where('expires_at', '>', now())->lockForUpdate()->first();
            if (!$token) return response()->json(['success' => false, 'code' => 401, 'message' => 'Invalid refresh token'], 401);

            $token->update(['revoked_at' => now()]);
            $access = $token->user->createToken('access', ['*'], now()->addMinutes(60))->plainTextToken;
            $refresh = Str::random(80);
            RefreshToken::create(['user_id' => $token->user_id, 'token_hash' => hash('sha256', $refresh), 'expires_at' => now()->addDays(30)]);
            return response()->json(['success' => true, 'code' => 200, 'message' => 'Token refreshed successfully', 'data' => ['access_token' => $access, 'refresh_token' => $refresh, 'token_type' => 'Bearer', 'expires_in' => 3600]]);
        });
    }

    public function logout(): JsonResponse
    {
        request()->user()->currentAccessToken()?->delete();
        request()->user()->refreshTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        return response()->json(['success' => true, 'code' => 200, 'message' => 'Logout successful']);
    }
}
