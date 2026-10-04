<?php

namespace App\Http\Requests;

use App\Models\MaintenanceTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'incident_id' => ['required', 'exists:incidents,id'],
            'technician_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'planned_at' => ['required', 'date'],
            'completed_at' => ['nullable', 'date', 'after_or_equal:planned_at', 'required_if:status,done'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'status' => ['required', Rule::in(array_keys(MaintenanceTask::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'incident_id' => 'incident', 'technician_id' => 'technicien', 'title' => 'titre',
            'planned_at' => 'date prévue', 'completed_at' => 'date de fin', 'cost' => 'coût',
            'status' => 'statut', 'notes' => 'notes',
        ];
    }

    public function messages(): array
    {
        return ['completed_at.required_if' => 'La date de fin est obligatoire pour une tâche terminée.'];
    }
}
