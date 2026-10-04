<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncidentRequest;
use App\Models\Equipment;
use App\Models\Incident;
use App\Services\Ai\IncidentTriage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** MODULE 4 — Incidents & maintenance (front office). */
class IncidentController extends Controller
{
    public function index(): View
    {
        return view('front.incidents.index', [
            'incidents' => Incident::where('reporter_id', auth()->id())
                ->with(['equipment', 'tasks'])->latest()->paginate(8),
        ]);
    }

    public function create(Request $request): View
    {
        // Équipements que l'utilisateur a loué ou possède
        $mine = Equipment::query()
            ->where('owner_id', auth()->id())
            ->orWhereHas('reservations', fn ($q) => $q->where('user_id', auth()->id()))
            ->orderBy('title')->get();

        return view('front.incidents.create', [
            'equipmentList' => $mine,
            'selected' => $request->integer('equipment') ?: null,
        ]);
    }

    public function store(IncidentRequest $request, IncidentTriage $triage): RedirectResponse
    {
        $result = $triage->triage($request->title, $request->description);

        Incident::create([
            'equipment_id' => $request->equipment_id,
            'reservation_id' => $request->reservation_id,
            'reporter_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            // Si l'utilisateur n'a pas modifié la proposition de l'IA, on garde la sienne
            'category' => $request->category ?: $result['category'],
            'severity' => $request->severity ?: $result['severity'],
            'status' => 'open',
            'ai_summary' => $result['summary'],
            'ai_advice' => $result['advice'],
        ]);

        return redirect()->route('front.incidents.index')->with('success', 'Incident signalé. Un technicien va le traiter.');
    }

    /** AJAX : triage IA pré-remplissant catégorie et gravité. */
    public function triage(Request $request, IncidentTriage $triage): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        return response()->json($triage->triage($data['title'], $data['description']));
    }
}
