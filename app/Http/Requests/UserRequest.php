<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'regex:/^[0-9+\s]{8,15}$/'],
            'city' => ['nullable', 'string', 'max:80'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $user = $this->route('user');
                if ($user?->isLastAdmin() && $this->input('role') !== 'admin') {
                    $validator->errors()->add('role', 'Impossible de retirer le rôle du dernier administrateur.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nom', 'email' => 'e-mail', 'phone' => 'téléphone', 'city' => 'ville', 'role' => 'rôle', 'password' => 'mot de passe'];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Numéro de téléphone invalide.'];
    }
}
