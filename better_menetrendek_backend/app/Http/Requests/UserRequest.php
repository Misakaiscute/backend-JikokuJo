<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
        //login
        if ($this->is('*/login') || $this->isMethod('POST') && str_contains($this->path(), 'login')) {
            return [
                'email'    => ['required', 'email'],
                'password' => ['required', 'string'],
            ];
        }

        //registration
        if ($this->isMethod('POST')) {
            return [
                'first_name'    => ['required', 'string', 'max:255'],
                'second_name'   => ['required', 'string', 'max:255'],
                'email'         => ['required', 'email', 'unique:users'],
                'password'      => ['required', 'min:8', 'confirmed'],
            ];
        }

        //update
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
}
