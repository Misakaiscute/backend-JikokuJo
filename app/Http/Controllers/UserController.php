<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Trip;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserRequest;
use Carbon\Carbon;
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
                'errors' => ['Invalid email or password'],
            ], 401, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        Auth::login($user);
        $request->session()->regenerate();

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
        $trip_id = $request->trip_id;
        try
        {
            Trip::findOrFail($trip_id);
        }
        catch(Exception $e)
        {
            return response()->json([
            'data'   => [],
            'errors' => ["Nincs trip ilyen id-vel."]
        ], 400, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $request->user()->favourites()->toggle($trip_id);

        return response()->json([
            'data'   => [],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function favourites(UserRequest $request)
    {
        $user = $request->user();
        $favourites = $user->favourites;
        if(!$favourites)
        {
            return response()->json([
            'data'   => ['Nincs egy kedvenc trip sem.'],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return response()->json([
            'data'   => [
                'favourites' => $favourites
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
