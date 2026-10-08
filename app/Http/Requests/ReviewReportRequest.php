<?php

namespace App\Http\Requests;

use App\Models\ReviewReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'reason' => ['required', Rule::in(array_keys(ReviewReport::REASONS))],
            'details' => ['nullable', 'string', 'max:500', 'required_if:reason,other'],
        ];

        if ($this->routeIs('admin.*')) {
            $rules['review_id'] = ['required', 'exists:reviews,id'];
            $rules['user_id'] = ['required', 'exists:users,id'];
            $rules['status'] = ['required', Rule::in(array_keys(ReviewReport::STATUSES))];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return ['reason' => 'motif', 'details' => 'détails', 'review_id' => 'avis', 'user_id' => 'auteur du signalement', 'status' => 'statut'];
    }

    public function messages(): array
    {
        return ['details.required_if' => 'Merci de préciser le motif "Autre".'];
    }
}
