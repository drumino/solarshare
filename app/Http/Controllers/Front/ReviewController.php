<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewReportRequest;
use App\Http\Requests\ReviewRequest;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\Reservation;
use App\Services\Ai\ReviewAnalyzer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** MODULE 3 — Avis & signalements (front office). */
class ReviewController extends Controller
{
    public function create(Reservation $reservation): View|RedirectResponse
    {
        if ($error = $this->cannotReview($reservation)) {
            return redirect()->route('front.reservations.show', $reservation)->with('error', $error);
        }

        return view('front.reviews.create', ['reservation' => $reservation->load('equipment')]);
    }

    public function store(ReviewRequest $request, Reservation $reservation, ReviewAnalyzer $analyzer): RedirectResponse
    {
        if ($error = $this->cannotReview($reservation)) {
            return redirect()->route('front.reservations.show', $reservation)->with('error', $error);
        }

        // Analyse IA : sentiment + modération automatique
        $analysis = $analyzer->analyze($request->comment, (int) $request->rating);

        Review::create([
            'reservation_id' => $reservation->id,
            'equipment_id' => $reservation->equipment_id,
            'user_id' => $request->user()->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'sentiment' => $analysis['sentiment'],
            'sentiment_score' => $analysis['score'],
            'is_visible' => ! $analysis['toxic'],
            'moderation_note' => $analysis['note'],
        ]);

        return redirect()->route('front.reservations.show', $reservation)->with(
            $analysis['toxic'] ? 'error' : 'success',
            $analysis['toxic']
                ? 'Votre avis a été enregistré mais mis en attente de modération ('.$analysis['note'].').'
                : 'Merci pour votre avis !'
        );
    }

    public function report(ReviewReportRequest $request, Review $review): RedirectResponse
    {
        if ($review->user_id === $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas signaler votre propre avis.');
        }

        if ($review->reports()->where('user_id', $request->user()->id)->exists()) {
            return back()->with('error', 'Vous avez déjà signalé cet avis.');
        }

        ReviewReport::create($request->validated() + [
            'review_id' => $review->id,
            'user_id' => $request->user()->id,
            'status' => 'open',
        ]);

        return back()->with('success', 'Signalement envoyé à la modération.');
    }

    private function cannotReview(Reservation $reservation): ?string
    {
        abort_unless($reservation->user_id === auth()->id(), 403);

        if (! in_array($reservation->status, ['completed'], true)) {
            return 'Vous pourrez donner votre avis une fois la location terminée.';
        }
        if ($reservation->review()->exists()) {
            return 'Vous avez déjà donné votre avis pour cette location.';
        }

        return null;
    }
}
