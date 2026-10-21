<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerReplyRequest;
use App\Models\Review;
use App\Services\Ai\ReplySuggester;
use App\Services\Ai\ReviewAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewReplyController extends Controller
{
    public function index(): View
    {
        $reviews = Review::query()
            ->whereHas('equipment', fn ($q) => $q->where('owner_id', auth()->id()))
            ->with('equipment', 'user')
            ->latest()
            ->paginate(10);
        return view('owner.reviews.index', compact('reviews'));
    }

    public function store(StoreOwnerReplyRequest $request, Review $review, ReviewAnalyzer $analyzer): RedirectResponse
    {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('reply', $review), 403);

        $analysis = $analyzer->analyze($request->reply, 5);
        if ($analysis['toxic']) {
            return back()->with('error', 'Votre réponse contient un contenu inapproprié.');
        }

        $wasNew = $review->owner_reply === null;
        $now = now();
        if ($wasNew) {
            $review->update([
                'owner_reply' => $request->reply,
                'owner_reply_at' => $now,
                'owner_reply_by' => auth()->id(),
            ]);
            $review->user?->notify(new \App\Notifications\OwnerRepliedNotification($review));
        } else {
            $review->update([
                'owner_reply' => $request->reply,
                'owner_reply_edited_at' => $now,
            ]);
        }

        return back()->with('success', 'Réponse enregistrée.');
    }

    public function suggest(Review $review, ReplySuggester $suggester): JsonResponse
    {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('reply', $review), 403);
        $result = $suggester->suggest($review);
        return response()->json($result);
    }
}
