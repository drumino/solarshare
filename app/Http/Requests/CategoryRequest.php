<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id;

        return [
            'name' => ['required', 'string', 'min:3', 'max:60', Rule::unique('categories', 'name')->ignore($id)],
            'icon' => ['required', 'string', 'regex:/^bi-[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nom', 'icon' => 'icône', 'description' => 'description'];
    }

    public function messages(): array
    {
        return ['icon.regex' => 'L\'icône doit être une classe Bootstrap Icons (ex. bi-sun).'];
    }
}
