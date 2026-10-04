<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnergyAdvisorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appliances' => ['required', 'array', 'min:1', 'max:15'],
            'appliances.*.name' => ['required', 'string', 'max:60'],
            'appliances.*.watts' => ['required', 'integer', 'min:1', 'max:5000'],
            'appliances.*.hours' => ['required', 'numeric', 'min:0.1', 'max:24'],
            'days' => ['required', 'integer', 'between:1,'.config('solarshare.max_rental_days')],
            'context' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function attributes(): array
    {
        return [
            'appliances' => 'appareils', 'appliances.*.name' => 'nom de l\'appareil',
            'appliances.*.watts' => 'puissance (W)', 'appliances.*.hours' => 'heures par jour',
            'days' => 'durée', 'context' => 'contexte',
        ];
    }
}
