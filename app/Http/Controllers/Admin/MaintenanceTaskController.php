<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaintenanceTaskRequest;
use App\Models\Incident;
use App\Models\MaintenanceTask;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** MODULE 4 — CRUD Tâches de maintenance (jointure Incident + Equipment + User). */
class MaintenanceTaskController extends Controller
{
    public function index(Request $request): View
    {
        $tasks = MaintenanceTask::query()
            ->join('incidents', 'incidents.id', '=', 'maintenance_tasks.incident_id')
            ->join('equipment', 'equipment.id', '=', 'incidents.equipment_id')
            ->leftJoin('users', 'users.id', '=', 'maintenance_tasks.technician_id')
            ->select('maintenance_tasks.*', 'incidents.title as incident_title', 'equipment.title as equipment_title', 'users.name as technician_name')
            ->when($request->status, fn ($q, $v) => $q->where('maintenance_tasks.status', $v))
            ->when($request->q, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('maintenance_tasks.title', 'like', "%{$v}%")
                ->orWhere('equipment.title', 'like', "%{$v}%")))
            ->latest('maintenance_tasks.planned_at')
            ->paginate(10)->withQueryString();

        return view('admin.maintenance-tasks.index', compact('tasks'));
    }

    public function create(Request $request): View
    {
        return view('admin.maintenance-tasks.form', $this->formData(new MaintenanceTask([
            'incident_id' => $request->integer('incident') ?: null,
            'status' => 'planned',
            'planned_at' => today(),
        ])));
    }

    public function store(MaintenanceTaskRequest $request): RedirectResponse
    {
        $task = MaintenanceTask::create($this->data($request));
        $this->syncStates($task);

        return redirect()->route('admin.maintenance-tasks.index')->with('success', 'Tâche créée.');
    }

    public function edit(MaintenanceTask $maintenanceTask): View
    {
        return view('admin.maintenance-tasks.form', $this->formData($maintenanceTask));
    }

    public function update(MaintenanceTaskRequest $request, MaintenanceTask $maintenanceTask): RedirectResponse
    {
        $maintenanceTask->update($this->data($request));
        $this->syncStates($maintenanceTask);

        return redirect()->route('admin.maintenance-tasks.index')->with('success', 'Tâche mise à jour.');
    }

    public function destroy(MaintenanceTask $maintenanceTask): RedirectResponse
    {
        $maintenanceTask->delete();

        return back()->with('success', 'Tâche supprimée.');
    }

    private function data(MaintenanceTaskRequest $request): array
    {
        $data = $request->validated();
        $data['cost'] = $data['cost'] ?? 0;

        return $data;
    }

    /** Synchronise l'état de l'incident et de l'équipement avec l'avancement des tâches. */
    private function syncStates(MaintenanceTask $task): void
    {
        $incident = $task->incident;
        $equipment = $incident->equipment;

        $allDone = $incident->tasks()->where('status', '!=', 'done')->doesntExist();

        if ($allDone) {
            $incident->update(['status' => 'resolved']);
            if ($equipment->status === 'maintenance') {
                $equipment->update(['status' => 'available']);
            }
        } else {
            $incident->update(['status' => 'in_progress']);
            if ($task->status === 'in_progress' && $equipment->status === 'available') {
                $equipment->update(['status' => 'maintenance']);
            }
        }
    }

    private function formData(MaintenanceTask $task): array
    {
        return [
            'task' => $task,
            'incidents' => Incident::with('equipment')->latest()->get(),
            'technicians' => User::orderBy('name')->get(),
        ];
    }
}
