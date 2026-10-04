<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\EquipmentRequest;
use App\Models\Category;
use App\Models\Equipment;
use App\Services\Ai\EquipmentAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** MODULE 1 — Catalogue d'équipements (front office). */
class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'category' => ['nullable', 'exists:categories,id'],
            'type' => ['nullable', 'in:'.implode(',', array_keys(Equipment::TYPES))],
            'city' => ['nullable', 'string', 'max:80'],
            'max_price' => ['nullable', 'numeric', 'min:1'],
            'sort' => ['nullable', 'in:recent,price_asc,price_desc,power'],
        ]);

        // Jointure avec categories pour pouvoir chercher sur le nom de catégorie
        $equipment = Equipment::query()
            ->join('categories', 'categories.id', '=', 'equipment.category_id')
            ->select('equipment.*', 'categories.name as category_name')
            ->where('equipment.status', 'available')
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('equipment.title', 'like', "%{$v}%")
                ->orWhere('categories.name', 'like', "%{$v}%")))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('equipment.category_id', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('equipment.type', $v))
            ->when($filters['city'] ?? null, fn ($q, $v) => $q->where('equipment.city', 'like', "%{$v}%"))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('equipment.price_per_day', '<=', $v))
            ->when($filters['sort'] ?? 'recent', fn ($q, $v) => match ($v) {
                'price_asc' => $q->orderBy('equipment.price_per_day'),
                'price_desc' => $q->orderByDesc('equipment.price_per_day'),
                'power' => $q->orderByDesc('equipment.power_watts'),
                default => $q->latest('equipment.created_at'),
            })
            ->with('category')
            ->paginate(9)
            ->withQueryString();

        return view('front.equipment.index', [
            'equipment' => $equipment,
            'categories' => Category::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function show(Equipment $equipment): View
    {
        abort_unless($equipment->status === 'available' || $equipment->owner_id === auth()->id() || auth()->user()?->isAdmin(), 404);

        $equipment->load(['category', 'owner']);

        return view('front.equipment.show', [
            'equipment' => $equipment,
            'reviews' => $equipment->reviews()->where('is_visible', true)->with('user')->latest()->get(),
            'booked' => $equipment->reservations()->active()
                ->whereDate('end_date', '>=', today())->orderBy('start_date')->get(['start_date', 'end_date']),
        ]);
    }

    public function create(): View
    {
        return view('front.equipment.create', [
            'categories' => Category::orderBy('name')->get(),
            'equipment' => new Equipment(['condition' => 'bon']),
        ]);
    }

    public function store(EquipmentRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['deposit'] = $data['deposit'] ?? 0;
        $data['owner_id'] = $request->user()->id;
        $data['status'] = 'pending'; // validation par l'administrateur

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('equipment', 'public');
        }

        Equipment::create($data);

        return redirect()->route('front.equipment.index')
            ->with('success', 'Votre équipement a été soumis. Il sera visible après validation par un administrateur.');
    }

    /* ---------- Fonctionnalités IA (AJAX) ---------- */

    public function aiDescription(Request $request, EquipmentAssistant $assistant): JsonResponse
    {
        return response()->json($assistant->describe($this->aiInput($request)));
    }

    public function aiPrice(Request $request, EquipmentAssistant $assistant): JsonResponse
    {
        return response()->json($assistant->suggestPrice($this->aiInput($request)));
    }

    private function aiInput(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(Equipment::TYPES))],
            'title' => ['nullable', 'string', 'max:120'],
            'power_watts' => ['nullable', 'integer', 'min:0'],
            'capacity_wh' => ['nullable', 'integer', 'min:0'],
            'condition' => ['nullable', 'in:'.implode(',', array_keys(Equipment::CONDITIONS))],
            'city' => ['nullable', 'string', 'max:80'],
        ]);
    }
}
