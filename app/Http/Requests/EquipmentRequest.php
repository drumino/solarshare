<?php

namespace App\Http\Requests;

use App\Models\Equipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(array_keys(Equipment::TYPES))],
            // Champs conditionnels selon le type d'équipement
            'power_watts' => ['nullable', 'integer', 'min:1', 'max:20000',
                Rule::requiredIf(in_array($this->input('type'), ['solar_panel', 'wind', 'inverter'], true))],
            'capacity_wh' => ['nullable', 'integer', 'min:1', 'max:100000',
                Rule::requiredIf($this->input('type') === 'battery')],
            'price_per_day' => ['required', 'numeric', 'min:1', 'max:1000'],
            'deposit' => ['nullable', 'numeric', 'min:0', 'max:20000'],
            'condition' => ['required', Rule::in(array_keys(Equipment::CONDITIONS))],
            'city' => ['required', 'string', 'max:80'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=200,min_height=200'],
        ];

        if ($this->routeIs('admin.*')) {
            $rules['owner_id'] = ['required', 'exists:users,id'];
            $rules['status'] = ['required', Rule::in(array_keys(Equipment::STATUSES))];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'catégorie', 'title' => 'titre', 'description' => 'description',
            'type' => 'type', 'power_watts' => 'puissance (W)', 'capacity_wh' => 'capacité (Wh)',
            'price_per_day' => 'prix par jour', 'deposit' => 'caution', 'condition' => 'état',
            'city' => 'ville', 'image' => 'photo', 'owner_id' => 'propriétaire', 'status' => 'statut',
        ];
    }
}
