<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
        ];

        if ($this->routeIs('admin.*')) {
            $id = $this->route('review')?->id;
            $rules['reservation_id'] = ['required', 'exists:reservations,id', Rule::unique('reviews', 'reservation_id')->ignore($id)];
            $rules['is_visible'] = ['required', 'boolean'];
            $rules['moderation_note'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'rating' => 'note', 'comment' => 'commentaire', 'reservation_id' => 'réservation',
            'is_visible' => 'visibilité', 'moderation_note' => 'note de modération',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.between' => 'La note doit être comprise entre 1 et 5.',
            'reservation_id.unique' => 'Cette réservation a déjà un avis.',
        ];
    }
}
