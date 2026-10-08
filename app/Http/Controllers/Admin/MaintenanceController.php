<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Read-only oversight of availability blocks for administrators. */
class MaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $records = MaintenanceRecord::query()
            ->with(['equipment:id,title,owner_id', 'equipment.owner:id,name', 'reporter:id,name'])
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->q, fn ($query, $term) => $query->whereHas('equipment', fn ($equipment) => $equipment->where('title', 'like', "%{$term}%")))
            ->latest('starts_on')
            ->paginate(15)
            ->withQueryString();

        return view('admin.maintenance.index', compact('records'));
    }
}
