<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReservationRequest;
use App\Models\Equipment;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** MODULE 2 — Réservations & paiements (front office). */
class ReservationController extends Controller
{
    public function index(): View
    {
        return view('front.reservations.index', [
            'reservations' => auth()->user()->reservations()->with('equipment.category')->latest()->paginate(8),
        ]);
    }

    public function store(ReservationRequest $request): RedirectResponse
    {
        $equipment = Equipment::findOrFail($request->equipment_id);
        $days = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) + 1;

        $reservation = Reservation::create([
            'equipment_id' => $equipment->id,
            'user_id' => $request->user()->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
            'total_price' => $days * $equipment->price_per_day,
            'status' => 'pending',
        ]);

        return redirect()->route('front.reservations.show', $reservation)
            ->with('success', 'Réservation créée. Procédez au paiement pour la confirmer.');
    }

    public function show(Reservation $reservation): View
    {
        $this->authorizeOwner($reservation);

        return view('front.reservations.show', [
            'reservation' => $reservation->load(['equipment.owner', 'payments', 'review']),
        ]);
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        $this->authorizeOwner($reservation);

        if (! in_array($reservation->status, ['pending', 'confirmed'], true)) {
            return back()->with('error', 'Cette réservation ne peut plus être annulée.');
        }

        $reservation->update(['status' => 'cancelled']);
        $reservation->payments()->where('status', 'paid')->update(['status' => 'refunded']);

        return back()->with('success', 'Réservation annulée.');
    }

    /** Paiement simulé : crée un Payment "paid" et confirme la réservation. */
    public function pay(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeOwner($reservation);

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
        ], [], ['method' => 'mode de paiement']);

        if ($reservation->status !== 'pending' || $reservation->is_paid) {
            return back()->with('error', 'Cette réservation est déjà réglée ou n\'est plus payable.');
        }

        $isCash = $data['method'] === 'cash';

        $reservation->payments()->create([
            'amount' => $reservation->total_price,
            'method' => $data['method'],
            'status' => $isCash ? 'pending' : 'paid',
            'reference' => 'SS-'.strtoupper(Str::random(8)),
            'paid_at' => $isCash ? null : now(),
        ]);

        $reservation->update(['status' => 'confirmed']);

        return back()->with('success', $isCash
            ? 'Réservation confirmée : règlement en espèces à la remise du matériel.'
            : 'Paiement reçu, réservation confirmée !');
    }

    private function authorizeOwner(Reservation $reservation): void
    {
        abort_unless($reservation->user_id === auth()->id(), 403);
    }
}
