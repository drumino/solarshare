<?php

namespace App\Http\Requests;

use App\Models\MaintenanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:4', 'max:120'],
            'reason' => ['required', Rule::in(array_keys(MaintenanceRecord::REASONS))],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'status' => ['required', Rule::in(array_keys(MaintenanceRecord::STATUSES))],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'starts_on' => 'date de début',
            'ends_on' => 'date de fin',
            'reason' => 'motif',
            'resolution_notes' => 'notes de résolution',
        ];
    }
}
