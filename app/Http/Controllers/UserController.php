<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserRequest;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use Exception;

class UserController extends Controller
{
    public function login(UserRequest $request, ?bool $rememberUser = false)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $password ? $user->password : '')) {
            return response()->json([
                'data'   => [],
                'errors' => ['Hibás email cím vagy jelszó.'],
            ], 401, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        //Set the session cookie for browsers
        Auth::login($user);
        $request->session()->regenerate();

        //Create the token for mobile devices
        $user->tokens()->delete();
        $expiresAt = Carbon::now()->addDays($rememberUser ? 14 : 1);
        $token = $user->createToken(
            'access_token',
            ['*'],
            $expiresAt
        );
        
        return response()->json([
            'data'      => ['token' => $token->plainTextToken],
            'errors'    => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function log_out(UserRequest $request)
    {
        if ($user = $request->user()) {

            // Token (API)
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }
    
            // Session (web)
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'data'      => [],
            'errors'    => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function get(UserRequest $request) {
        return response()->json([
            'data' => [
                'user' => $request->user(),
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function store(UserRequest $request)
    {
        $user = User::create($request->all());

        return response()->json([
            'data'   => ['user' => $user],
            'errors' => []
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
            'data' => [
                'user' => $user->refresh()->only([
                    'id',
                    'first_name',
                    'second_name',
                    'email',
                ])
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function destroy(UserRequest $request)
    {
        $user = $request->user();

        $user->delete();

        return response()->json([
            'data'   => [],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function toggleFavourite(UserRequest $request)
    {
        $user = $request->user();
        $route_id = $request->route_id;
        $time = $request->time;
        try
        {
            Route::findOrFail($route_id);
        }
        catch(Exception $e)
        {
            return response()->json([
            'data'   => [],
            'errors' => ["Nincs route ilyen id-vel."]
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $exists = $user->favourites()
                   ->wherePivot('route_id', $route_id)
                   ->wherePivot('time', $time)
                   ->exists();
        if ($exists) 
            {
            $user->favourites()
                ->wherePivot('route_id', $route_id)
                ->wherePivot('time', $time)
                ->detach();
                return response()->json([
                    'data'   => [
                        'route_id'   => $route_id,
                        'new_status' => false
                    ],
                    'errors' => []
                ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } 
        else 
        {
            $user->favourites()->attach($route_id, [
                'time' => $time
            ]);
            return response()->json([
                'data'   => [
                    'route_id'   => $route_id,
                    'new_status' => true
                ],
                'errors' => []
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
    }

    public function favourites(UserRequest $request)
    {
        $user = $request->user();

        $favourites = $user->favourites()
                        ->get()
                        ->map(function ($route) {
                            return [
                                'route'    => $route,
                                'time'     => $route->pivot->time,
                            ];
                        });

        return response()->json([
            'data'   => [
                'favourites' => $favourites
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
