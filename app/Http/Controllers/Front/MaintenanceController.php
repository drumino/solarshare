<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaintenanceRecordRequest;
use App\Models\Equipment;
use App\Models\MaintenanceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** MODULE — Disponibilité et maintenance, géré par le propriétaire. */
class MaintenanceController extends Controller
{
    public function index(): View
    {
        $equipment = auth()->user()->equipment()
            ->withCount(['maintenanceRecords as active_maintenance_count' => fn ($query) => $query->blockingOn(today())])
            ->with(['maintenanceRecords' => fn ($query) => $query->latest('starts_on')->limit(3)])
            ->latest()
            ->get();

        return view('front.maintenance.index', compact('equipment'));
    }

    public function manage(Equipment $equipment): View
    {
        $this->ensureOwner($equipment);

        return view('front.maintenance.manage', [
            'equipment' => $equipment,
            'records' => $equipment->maintenanceRecords()->latest('starts_on')->get(),
            'record' => new MaintenanceRecord([
                'starts_on' => today(),
                'ends_on' => today(),
                'status' => 'scheduled',
                'reason' => 'scheduled_maintenance',
            ]),
        ]);
    }

    public function store(MaintenanceRecordRequest $request, Equipment $equipment): RedirectResponse
    {
        $this->ensureOwner($equipment);
        $data = $request->validated();

        if ($this->overlapsBlockingRecord($equipment, $data)) {
            return back()->withInput()->with('error', 'Cette période chevauche déjà une indisponibilité planifiée ou en cours.');
        }

        $equipment->maintenanceRecords()->create($data + ['reported_by' => $request->user()->id]);

        return back()->with('success', 'Indisponibilité enregistrée.');
    }

    public function update(MaintenanceRecordRequest $request, Equipment $equipment, MaintenanceRecord $record): RedirectResponse
    {
        $this->ensureOwner($equipment);
        abort_unless($record->equipment_id === $equipment->id, 404);
        $data = $request->validated();

        if ($this->overlapsBlockingRecord($equipment, $data, $record)) {
            return back()->withInput()->with('error', 'Cette période chevauche déjà une indisponibilité planifiée ou en cours.');
        }

        $record->update($data);

        return back()->with('success', 'Intervention mise à jour.');
    }

    public function destroy(Equipment $equipment, MaintenanceRecord $record): RedirectResponse
    {
        $this->ensureOwner($equipment);
        abort_unless($record->equipment_id === $equipment->id, 404);
        $record->delete();

        return back()->with('success', 'Indisponibilité supprimée.');
    }

    private function ensureOwner(Equipment $equipment): void
    {
        abort_unless($equipment->owner_id === auth()->id(), 403);
    }

    private function overlapsBlockingRecord(Equipment $equipment, array $data, ?MaintenanceRecord $ignore = null): bool
    {
        if (in_array($data['status'], ['completed', 'cancelled'], true)) {
            return false;
        }

        return $equipment->maintenanceRecords()
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
            ->whereDate('starts_on', '<=', Carbon::parse($data['ends_on']))
            ->whereDate('ends_on', '>=', Carbon::parse($data['starts_on']))
            ->exists();
    }
}
