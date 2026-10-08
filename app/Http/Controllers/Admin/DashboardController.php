<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'kpis' => [
                'users' => User::count(),
                'admins' => User::where('role', 'admin')->count(),
                'members' => User::where('role', 'user')->count(),
                'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
                'categories' => Category::count(),
                'equipment' => Equipment::count(),
                'available_equipment' => Equipment::available()->count(),
                'pending_equipment' => Equipment::where('status', 'pending')->count(),
            ],
            'byRole' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
            'byCity' => User::whereNotNull('city')->selectRaw('city, count(*) as total')->groupBy('city')->orderByDesc('total')->pluck('total', 'city'),
            'byType' => Equipment::selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'byStatus' => Equipment::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'latestUsers' => User::latest()->take(6)->get(),
        ]);
    }
}
