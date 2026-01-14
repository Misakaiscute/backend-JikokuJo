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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        //Toggle favourite route
        if ($this->is('*/favourites/toggle') || 
            ($this->isMethod('POST') && str_contains($this->path(), 'favourites/toggle'))) {
            return [
                'route_id' => ['required', 'integer', 'exists:routes,id'],
                'minutes'  => ['required', 'integer', 'min:1', 'max:1440'],
            ];
        }

        //Login
        if ($this->is('*/login') || 
            ($this->isMethod('POST') && str_contains($this->path(), 'login'))) {
            return [
                'email'    => ['required', 'email'],
                'password' => ['required', 'string'],
            ];
        }

        //Registration
        if ($this->isMethod('POST') && str_contains($this->path(), 'register')) {
            return [
                'first_name'    => ['required', 'string', 'max:255'],
                'second_name'   => ['required', 'string', 'max:255'],
                'email'         => ['required', 'email', 'unique:users'],
                'password'      => ['required', 'min:8', 'confirmed'],
            ];
        }

        //Update
        return [
            'first_name'    => ['sometimes', 'string', 'max:255'],
            'second_name'   => ['sometimes', 'string', 'max:255'],
            'email'         => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($this->user()?->id),
            ],
            'password'      => ['sometimes', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'route_id.exists' => 'The specified route does not exist.',
            'minutes.min'     => 'The duration must be at least 1 minute.',
            'minutes.max'     => 'The duration can be a maximum of 24 hours (1440 minutes).',
            'first_name.max'  => 'The first name may not be greater than 255 characters.',
            'first_name.required' => 'The first name field is required.',
            'second_name.max'  => 'The last name may not be greater than 255 characters.',
            'second_name.required' => 'The last name field is required.',
            'email.unique'    => 'A user with this email address already exists.',
            'email.required'  => 'The email address field is required.',
            'email.email'     => 'The email address format is invalid.',
            'password.required' => 'The password field is required.',
            'password.min'    => 'The password must be at least 8 characters long.',
            'password_confirmation.required' => 'Please confirm your password by entering it again.',
        ];
    }
}
