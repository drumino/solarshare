<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncidentRequest;
use App\Models\Equipment;
use App\Models\Incident;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Ai\IncidentTriage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** MODULE 4 — CRUD Incidents (jointure Equipment + User) avec triage IA. */
class IncidentController extends Controller
{
    public function index(Request $request): View
    {
        $incidents = Incident::query()
            ->join('equipment', 'equipment.id', '=', 'incidents.equipment_id')
            ->join('users', 'users.id', '=', 'incidents.reporter_id')
            ->select('incidents.*', 'equipment.title as equipment_title', 'users.name as reporter_name')
            ->withCount('tasks')
            ->when($request->q, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('incidents.title', 'like', "%{$v}%")
                ->orWhere('equipment.title', 'like', "%{$v}%")))
            ->when($request->severity, fn ($q, $v) => $q->where('incidents.severity', $v))
            ->when($request->status, fn ($q, $v) => $q->where('incidents.status', $v))
            ->latest('incidents.created_at')
            ->paginate(10)->withQueryString();

        return view('admin.incidents.index', compact('incidents'));
    }

    public function create(): View
    {
        return view('admin.incidents.form', $this->formData(new Incident(['status' => 'open', 'severity' => 'low', 'category' => 'other'])));
    }

    public function store(IncidentRequest $request, IncidentTriage $triage): RedirectResponse
    {
        $ai = $triage->triage($request->title, $request->description);

        Incident::create($request->validated() + ['ai_summary' => $ai['summary'], 'ai_advice' => $ai['advice']]);

        return redirect()->route('admin.incidents.index')->with('success', 'Incident créé.');
    }

    public function edit(Incident $incident): View
    {
        return view('admin.incidents.form', $this->formData($incident));
    }

    public function update(IncidentRequest $request, Incident $incident): RedirectResponse
    {
        $incident->update($request->validated());

        return redirect()->route('admin.incidents.index')->with('success', 'Incident mis à jour.');
    }

    public function destroy(Incident $incident): RedirectResponse
    {
        $incident->delete();

        return back()->with('success', 'Incident supprimé.');
    }

    private function formData(Incident $incident): array
    {
        return [
            'incident' => $incident,
            'equipmentList' => Equipment::orderBy('title')->get(),
            'users' => User::orderBy('name')->get(),
            'reservations' => Reservation::with('equipment')->latest()->take(100)->get(),
        ];
    }
}
