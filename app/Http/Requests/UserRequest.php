<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('POST') && str_contains($this->path(), 'routes/favourite/toggle')) {
            return [
                'route_id' => ['required_without:trip_id', 'string'],
                'trip_id'  => ['required_without:route_id', 'string'],
                'time'     => ['sometimes', 'string'],
            ];
        }

        if ($this->isMethod('POST') && str_contains($this->path(), 'login')) {
            return [
                'email'    => ['required', 'email'],
                'password' => ['required', 'string'],
                'remember_user' => ['sometimes', 'boolean'],
            ];
        }

        if ($this->isMethod('POST') && str_contains($this->path(), 'register')) {
            return [
                'first_name'    => ['required', 'string', 'max:255'],
                'second_name'   => ['required', 'string', 'max:255'],
                'email'         => ['required', 'email', 'unique:users'],
                'password'      => ['required', 'min:8', 'confirmed'],
            ];
        }

        return [
            'first_name'    => ['sometimes', 'string', 'max:255'],
            'second_name'   => ['sometimes', 'string', 'max:255'],
            'email'         => ['sometimes', 'email', Rule::unique('users')->ignore($this->user()?->id)],
            'password'      => ['sometimes', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'route_id.required_without' => 'A route_id vagy trip_id mező megadása kötelező.',
            'trip_id.required_without' => 'A route_id vagy trip_id mező megadása kötelező.',
            'first_name.max'  => 'A vezetéknév maximum 255 karakter lehet.',
            'first_name.required' => 'A vezetéknév mező kötelező.',
            'second_name.max'  => 'A keresztnév maximum 255 karakter lehet.',
            'second_name.required' => 'A keresztnév mező kötelező.',
            'email.unique'    => 'Ez az email címhez már tartozik fiók.',
            'email.required'  => 'Az email mező kötelező.',
            'email.email'     => 'Az email cím helytelen formátumban van.',
            'password.required' => 'A jelszó mező kötelező.',
            'password.min'    => 'A jelszónak minimum 8 karakternek kell lennie.',
            'password_confirmation.required' => 'A jelszó megerősítése kötelező.',
            'password.confirmed' => 'A két jelszó nem egyezik.'
        ];
    }
}
