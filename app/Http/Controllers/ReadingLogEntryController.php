<?php

namespace App\Http\Controllers;

use App\Enums\ReadingStatus;
use App\Models\ReadingLogEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ReadingLogEntryController extends Controller
{
    //    public function index()
    //    {
    //        //
    //    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'book_id' => ['required', Rule::exists('books', 'id')],
            'status' => ['required', Rule::enum(ReadingStatus::class)],
            'started_at' => 'nullable|date',
            'finished_at' => 'nullable|date',
        ]);

        $createdReadingLogEntry = ReadingLogEntry::create(
            [...$validated,
                'user_id' => $user->id,
            ]
        );

        return Inertia::flash([
            'toast' => [
                'type' => 'success',
                'message' => $createdReadingLogEntry->book->title.' added to reading log',
            ],
        ])->back();
    }

    //    public function show(ReadingLogEntry $readingLogEntry)
    //    {
    //        //
    //    }

    //    public function update(Request $request, ReadingLogEntry $readingLogEntry)
    //    {
    //        //
    //    }

    public function destroy(ReadingLogEntry $readingLogEntry): RedirectResponse
    {
        Gate::authorize('delete', $readingLogEntry);

        $readingLogEntry->delete();

        return Inertia::flash([
            'toast' => [
                'type' => 'success',
                'message' => $readingLogEntry->book->title.' removed from reading log',
            ],
        ])->back();
    }
}
