<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SearchController;
use App\Http\Requests\UserRequest;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\DeviceToken;

class UserController extends Controller
{
    public function login(UserRequest $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $rememberUser = (bool) ($request->input('remember_user') ?? false);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'data'   => [],
                'errors' => ['Hibás email cím vagy jelszó.'],
            ], 401, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($request->hasSession()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        // Ne töröljük a korábbi tokeneket: mobilon több aktív eszköz/token is lehet.
        $expiresAt = Carbon::now()->addDays($rememberUser ? 14 : 1);
        $token = $user->createToken('access_token', ['*'], $expiresAt);

        return response()->json([
            'data'   => ['token' => $token->plainTextToken],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function log_out(UserRequest $request)
    {
        if ($user = $request->user()) {
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            } else {
                $user->tokens()->delete();
            }
        }

        if ($request->hasSession() && Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'data'   => [],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function get(UserRequest $request)
    {
        return response()->json([
            'data' => ['user' => $request->user()],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function store(UserRequest $request)
    {
        $user = User::create($request->validated());

        return response()->json([
            'data'   => ['user' => $user],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function update(UserRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        $user->touch();

        return response()->json([
            'data' => [
                'user' => $user->refresh()->only(['id', 'first_name', 'second_name', 'email'])
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function destroy(UserRequest $request)
    {
        $request->user()->delete();

        return response()->json([
            'data'   => [],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function toggleFavourite(UserRequest $request)
    {
        $user = $request->user();
        $time = (string) ($request->input('time') ?? '');
        $routeId = $request->input('route_id');

        if (!$routeId && $request->filled('trip_id')) {
            $trip = Trip::find($request->input('trip_id'));
            if (!$trip) {
                return response()->json([
                    'data' => [],
                    'errors' => ['Nincs trip ilyen id-vel.']
                ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $routeId = $trip->route_id;
        }

        $route = Route::find($routeId);
        if (!$route) {
            return response()->json([
                'data' => [],
                'errors' => ['Nincs route ilyen id-vel.']
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $exists = $user->favourites()
            ->wherePivot('route_id', $routeId)
            ->wherePivot('time', $time)
            ->exists();

        if ($exists) {
            $user->favourites()
                ->wherePivot('route_id', $routeId)
                ->wherePivot('time', $time)
                ->detach();
        } else {
            $user->favourites()->attach($routeId, ['time' => $time]);
        }

        return response()->json([
            'data' => [
                'route' => [
                    'id' => $route->id,
                    'short_name' => $route->short_name,
                    'type' => SearchController::getRouteTypeCategory($route->type),
                    'color' => $route->color,
                ],
                'new_status' => !$exists,
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function favourites(UserRequest $request)
    {
        $favourites = $request->user()->favourites()
            ->get()
            ->map(function ($route) {
                return [
                    'route' => [
                        'id' => $route->id,
                        'short_name' => $route->short_name,
                        'type' => SearchController::getRouteTypeCategory($route->type),
                        'color' => $route->color,
                    ],
                    'time' => $route->pivot->time,
                ];
            });

        return response()->json([
            'data' => ['favourites' => $favourites],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function saveDeviceToken(UserRequest $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['nullable', 'string'],
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $validated['platform'] ?? 'android',
            ]
        );

        return response()->json([
                'data' => ['message' => 'Device token saved.'],
                'errors' => [],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
