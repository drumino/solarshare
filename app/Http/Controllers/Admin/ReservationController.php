<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReservationRequest;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** MODULE 2 — CRUD Réservations (jointure Equipment + User). */
class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $reservations = Reservation::query()
            ->join('equipment', 'equipment.id', '=', 'reservations.equipment_id')
            ->join('users', 'users.id', '=', 'reservations.user_id')
            ->select('reservations.*', 'equipment.title as equipment_title', 'users.name as user_name')
            ->withSum(['payments as paid_total' => fn ($q) => $q->where('status', 'paid')], 'amount')
            ->when($request->q, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('equipment.title', 'like', "%{$v}%")
                ->orWhere('users.name', 'like', "%{$v}%")))
            ->when($request->status, fn ($q, $v) => $q->where('reservations.status', $v))
            ->latest('reservations.created_at')
            ->paginate(10)->withQueryString();

        return view('admin.reservations.index', compact('reservations'));
    }

    public function create(): View
    {
        return view('admin.reservations.form', $this->formData(new Reservation(['status' => 'pending'])));
    }

    public function store(ReservationRequest $request): RedirectResponse
    {
        Reservation::create($request->validated() + ['total_price' => $this->total($request)]);

        return redirect()->route('admin.reservations.index')->with('success', 'Réservation créée.');
    }

    public function edit(Reservation $reservation): View
    {
        return view('admin.reservations.form', $this->formData($reservation));
    }

    public function update(ReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        $reservation->update($request->validated() + ['total_price' => $this->total($request)]);

        return redirect()->route('admin.reservations.index')->with('success', 'Réservation mise à jour.');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        $reservation->delete();

        return back()->with('success', 'Réservation supprimée.');
    }

    private function total(Request $request): float
    {
        $days = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) + 1;

        return $days * (float) Equipment::findOrFail($request->equipment_id)->price_per_day;
    }

    private function formData(Reservation $reservation): array
    {
        return [
            'reservation' => $reservation,
            'equipmentList' => Equipment::orderBy('title')->get(),
            'users' => User::orderBy('name')->get(),
        ];
    }
}
