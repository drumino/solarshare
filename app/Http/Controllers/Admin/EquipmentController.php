<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EquipmentRequest;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** MODULE 1 — CRUD Équipements (jointure Category + User). */
class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $equipment = Equipment::query()
            ->join('categories', 'categories.id', '=', 'equipment.category_id')
            ->join('users', 'users.id', '=', 'equipment.owner_id')
            ->select('equipment.*', 'categories.name as category_name', 'users.name as owner_name')
            ->when($request->q, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('equipment.title', 'like', "%{$v}%")
                ->orWhere('users.name', 'like', "%{$v}%")))
            ->when($request->category, fn ($q, $v) => $q->where('equipment.category_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('equipment.status', $v))
            ->latest('equipment.created_at')
            ->paginate(10)->withQueryString();

        return view('admin.equipment.index', [
            'equipment' => $equipment,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.equipment.form', $this->formData(new Equipment(['condition' => 'bon', 'status' => 'available'])));
    }

    public function store(EquipmentRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['deposit'] = $data['deposit'] ?? 0;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('equipment', 'public');
        }

        Equipment::create($data);

        return redirect()->route('admin.equipment.index')->with('success', 'Équipement créé.');
    }

    public function edit(Equipment $equipment): View
    {
        return view('admin.equipment.form', $this->formData($equipment));
    }

    public function update(EquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['deposit'] = $data['deposit'] ?? 0;

        if ($request->hasFile('image')) {
            if ($equipment->image_path) {
                Storage::disk('public')->delete($equipment->image_path);
            }
            $data['image_path'] = $request->file('image')->store('equipment', 'public');
        }

        $equipment->update($data);

        return redirect()->route('admin.equipment.index')->with('success', 'Équipement mis à jour.');
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        if ($equipment->reservations()->active()->exists()) {
            return back()->with('error', 'Suppression impossible : des réservations sont en cours.');
        }

        if ($equipment->image_path) {
            Storage::disk('public')->delete($equipment->image_path);
        }
        $equipment->delete();

        return back()->with('success', 'Équipement supprimé.');
    }

    private function formData(Equipment $equipment): array
    {
        return [
            'equipment' => $equipment,
            'categories' => Category::orderBy('name')->get(),
            'owners' => User::orderBy('name')->get(),
        ];
    }
}
