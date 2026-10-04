<?php

namespace App\Http\Requests;

use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('payment')?->id;

        return [
            'reservation_id' => ['required', 'exists:reservations,id'],
            'amount' => ['required', 'numeric', 'min:0.5', 'max:100000'],
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'status' => ['required', Rule::in(array_keys(Payment::STATUSES))],
            'reference' => ['nullable', 'string', 'max:40', Rule::unique('payments', 'reference')->ignore($id)],
            'paid_at' => ['nullable', 'date', 'required_if:status,paid'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $reservation = Reservation::find($this->input('reservation_id'));
            if ((float) $this->input('amount') > (float) $reservation->total_price) {
                $validator->errors()->add('amount', 'Le montant dépasse le total de la réservation ('.$reservation->total_price.' DT).');
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'reservation_id' => 'réservation', 'amount' => 'montant', 'method' => 'mode de paiement',
            'status' => 'statut', 'reference' => 'référence', 'paid_at' => 'date de paiement',
        ];
    }
}
