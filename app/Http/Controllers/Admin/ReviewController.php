<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\Ai\ReviewAnalyzer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** MODULE 3 — CRUD Avis (jointure Equipment + User) avec analyse IA. */
class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = Review::query()
            ->join('equipment', 'equipment.id', '=', 'reviews.equipment_id')
            ->join('users', 'users.id', '=', 'reviews.user_id')
            ->select('reviews.*', 'equipment.title as equipment_title', 'users.name as user_name')
            ->withCount('reports')
            ->when($request->sentiment, fn ($q, $v) => $q->where('reviews.sentiment', $v))
            ->when($request->visible !== null && $request->visible !== '', fn ($q) => $q->where('reviews.is_visible', $request->boolean('visible')))
            ->when($request->q, fn ($q, $v) => $q->where('reviews.comment', 'like', "%{$v}%"))
            ->latest('reviews.created_at')
            ->paginate(10)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function create(): View
    {
        return view('admin.reviews.form', $this->formData(new Review(['rating' => 5, 'is_visible' => true])));
    }

    public function store(ReviewRequest $request, ReviewAnalyzer $analyzer): RedirectResponse
    {
        $reservation = Reservation::findOrFail($request->reservation_id);
        $analysis = $analyzer->analyze($request->comment, (int) $request->rating);

        Review::create($request->validated() + [
            'equipment_id' => $reservation->equipment_id,
            'user_id' => $reservation->user_id,
            'sentiment' => $analysis['sentiment'],
            'sentiment_score' => $analysis['score'],
        ]);

        return redirect()->route('admin.reviews.index')->with('success', 'Avis créé (analyse IA : '.$analysis['sentiment'].').');
    }

    public function edit(Review $review): View
    {
        return view('admin.reviews.form', $this->formData($review));
    }

    public function update(ReviewRequest $request, Review $review, ReviewAnalyzer $analyzer): RedirectResponse
    {
        $reservation = Reservation::findOrFail($request->reservation_id);
        $extra = ['equipment_id' => $reservation->equipment_id, 'user_id' => $reservation->user_id];

        if ($request->comment !== $review->comment || (int) $request->rating !== (int) $review->rating) {
            $analysis = $analyzer->analyze($request->comment, (int) $request->rating);
            $extra += ['sentiment' => $analysis['sentiment'], 'sentiment_score' => $analysis['score']];
        }

        $review->update($request->validated() + $extra);

        return redirect()->route('admin.reviews.index')->with('success', 'Avis mis à jour.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Avis supprimé.');
    }

    private function formData(Review $review): array
    {
        return [
            'review' => $review,
            'reservations' => Reservation::with(['equipment', 'user'])
                ->where(fn ($q) => $q->whereDoesntHave('review')->orWhere('id', $review->reservation_id))
                ->latest()->get(),
        ];
    }
}
