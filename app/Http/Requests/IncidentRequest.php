<?php

namespace App\Http\Requests;

use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $admin = $this->routeIs('admin.*');

        $rules = [
            'equipment_id' => ['required', 'exists:equipment,id'],
            'reservation_id' => ['nullable', 'exists:reservations,id'],
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            // Sur le front, catégorie/gravité peuvent être déterminées automatiquement par l'IA
            'category' => [$admin ? 'required' : 'nullable', Rule::in(array_keys(Incident::CATEGORIES))],
            'severity' => [$admin ? 'required' : 'nullable', Rule::in(array_keys(Incident::SEVERITIES))],
        ];

        if ($admin) {
            $rules['reporter_id'] = ['required', 'exists:users,id'];
            $rules['status'] = ['required', Rule::in(array_keys(Incident::STATUSES))];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'equipment_id' => 'équipement', 'reservation_id' => 'réservation', 'title' => 'titre',
            'description' => 'description', 'category' => 'catégorie', 'severity' => 'gravité',
            'reporter_id' => 'déclarant', 'status' => 'statut',
        ];
    }
}
