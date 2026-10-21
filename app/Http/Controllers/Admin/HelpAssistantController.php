<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ai\HelpAssistant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HelpAssistantController extends Controller
{
    public function __construct(private HelpAssistant $assistant)
    {
    }

    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:300'],
            'page' => ['nullable', 'string'],
        ]);

        $result = $this->assistant->answer(
            $validated['question'],
            $validated['page'] ?? 'avis'
        );

        return response()->json([
            'answer' => $result['answer'],
            'engine' => $result['engine'],
        ]);
    }
}
