<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** MODULE 2 — CRUD Paiements (jointure Reservation + Equipment + User). */
class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->join('reservations', 'reservations.id', '=', 'payments.reservation_id')
            ->join('users', 'users.id', '=', 'reservations.user_id')
            ->join('equipment', 'equipment.id', '=', 'reservations.equipment_id')
            ->select('payments.*', 'users.name as user_name', 'equipment.title as equipment_title')
            ->when($request->status, fn ($q, $v) => $q->where('payments.status', $v))
            ->when($request->input('method'), fn ($q, $v) => $q->where('payments.method', $v))
            ->latest('payments.created_at')
            ->paginate(10)->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        return view('admin.payments.form', $this->formData(new Payment([
            'reservation_id' => $request->integer('reservation') ?: null,
            'status' => 'paid',
            'method' => 'card',
        ])));
    }

    public function store(PaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['reference'] = ($data['reference'] ?? null) ?: 'SS-'.strtoupper(Str::random(8));

        $payment = Payment::create($data);
        $this->syncReservation($payment);

        return redirect()->route('admin.payments.index')->with('success', 'Paiement enregistré.');
    }

    public function edit(Payment $payment): View
    {
        return view('admin.payments.form', $this->formData($payment));
    }

    public function update(PaymentRequest $request, Payment $payment): RedirectResponse
    {
        $payment->update($request->validated());
        $this->syncReservation($payment);

        return redirect()->route('admin.payments.index')->with('success', 'Paiement mis à jour.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return back()->with('success', 'Paiement supprimé.');
    }

    /** Un paiement validé confirme automatiquement une réservation en attente. */
    private function syncReservation(Payment $payment): void
    {
        $reservation = $payment->reservation;

        if ($payment->status === 'paid' && $reservation->status === 'pending') {
            $reservation->update(['status' => 'confirmed']);
        }
    }

    private function formData(Payment $payment): array
    {
        return [
            'payment' => $payment,
            'reservations' => Reservation::with(['equipment', 'user'])->latest()->get(),
        ];
    }
}
