<?php

namespace App\Http\Requests;

use App\Models\Equipment;
use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReservationRequest extends FormRequest
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
            'start_date' => ['required', 'date', $admin ? 'date' : 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        if ($admin) {
            $rules['user_id'] = ['required', 'exists:users,id'];
            $rules['status'] = ['required', Rule::in(array_keys(Reservation::STATUSES))];
        }

        return $rules;
    }

    /** Règles métier : durée max, disponibilité, chevauchement, propriétaire. */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $equipment = Equipment::find($this->input('equipment_id'));
            $start = Carbon::parse($this->input('start_date'));
            $end = Carbon::parse($this->input('end_date'));

            if ($start->diffInDays($end) + 1 > config('solarshare.max_rental_days')) {
                $validator->errors()->add('end_date', 'La durée maximale de location est de '.config('solarshare.max_rental_days').' jours.');
            }

            if (! $this->routeIs('admin.*')) {
                if ($equipment->status !== 'available') {
                    $validator->errors()->add('equipment_id', 'Cet équipement n\'est pas disponible à la location.');
                }
                if ($equipment->owner_id === $this->user()->id) {
                    $validator->errors()->add('equipment_id', 'Vous ne pouvez pas louer votre propre équipement.');
                }
            }

            $ignoreId = $this->route('reservation')?->id;
            $conflict = Reservation::active()
                ->where('equipment_id', $equipment->id)
                ->overlapping($start, $end)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();

            if ($conflict && ! in_array($this->input('status'), ['cancelled', 'completed'], true)) {
                $validator->errors()->add('start_date', 'Ces dates chevauchent une réservation existante.');
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'equipment_id' => 'équipement', 'start_date' => 'date de début', 'end_date' => 'date de fin',
            'notes' => 'remarques', 'user_id' => 'locataire', 'status' => 'statut',
        ];
    }
}
