<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserRequest;
use Carbon\Carbon;

class UserController extends Controller
{
    public function login(UserRequest $request, ?bool $rememberUser = false)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $password ? $user->password : '')) {
            return response()->json([
                'message' => 'Invalid email or password',
            ], 401, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $user->tokens()->delete();

        $expiresAt = Carbon::now()->addDays($rememberUser ? 7 : 1);

        $token = $user->createToken(
            'access_token',
            ['*'],
            $expiresAt
        );
        
        return response()->json([
            'token'      => $token->plainTextToken,
            'expires_at' => $expiresAt->toDateTimeString(),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function store(UserRequest $request)
    {
        $user = User::create($request->all());

        return response()->json([
            'user' => $user,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function update(UserRequest $request)
    {
        $user = $request->user();

        $validated = $request->all();

        if (isset($validated['password'])) 
        {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        $user->touch();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->refresh()->only([
                'id',
                'first_name',
                'second_name',
                'email',
            ])
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function destroy(UserRequest $request)
    {
        $user = $request->user();

        $userData = $request->user()->second_name . " " . $request->user()->first_name;

        $user->delete();

        return response()->json([
            'message' => 'Profile deleted successfully',
            'user' => $userData
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function toggleFavouriteRoute(UserRequest $request)
    {
        $request->validate([
            'route_id' => 'required|string|exists:routes,id',
            'minutes'  => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $routeId = $request->route_id;
        $minutes = $request->minutes;

        $saved = $this->parseSavedRoutes($user->saved_routes);

        if (isset($saved[$routeId])) 
        {
            unset($saved[$routeId]);
            $action = 'removed';
        } else {
            $saved[$routeId] = $minutes;
            $action = 'added';
        }

        $user->saved_routes = $this->formatSavedRoutes($saved);
        $user->save();

        return response()->json([
            'success' => true,
            'action'  => $action,
            'saved_routes' => $saved,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function parseSavedRoutes(?string $str): array
    {
        if (empty($str)) {
            return [];
        }

        $pairs = explode(';', $str);
        $result = [];

        foreach ($pairs as $pair) {
            if (empty($pair)) continue;
            [$id, $minutes] = explode(':', $pair, 2);
            $result[(int)$id] = (int)$minutes;
        }

        return $result;
    }

    private function formatSavedRoutes(array $routes): ?string
    {
        if (empty($routes)) {
            return null;
        }

        $parts = [];
        foreach ($routes as $id => $minutes) {
            $parts[] = "$id:$minutes";
        }

        return implode(';', $parts);
    }
}
