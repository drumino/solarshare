<?php

namespace App\Http\Requests;

use App\Models\Review;
use App\Models\ReviewReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_id' => ['nullable', 'exists:reviews,id'],
            'reason' => ['required', Rule::in(array_keys(ReviewReport::REASONS))],
            'details' => ['nullable', 'string', 'max:500', 'required_if:reason,other'],
        ];
    }
}
