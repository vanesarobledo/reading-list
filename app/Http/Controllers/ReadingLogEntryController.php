<?php

namespace App\Http\Controllers;

use App\Enums\ReadingStatus;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\ReadingLogEntry;
use Illuminate\Http\Request;
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
            'status' => [Rule::enum(ReadingStatus::class)],
            'started_at' => 'nullable|date',
            'finished_at' => 'nullable|date',
        ]);

        $createdReadingLogEntry = ReadingLogEntry::create(
            [...$validated,
                'user_id' => $user->id,
            ]
        );
        $book = BookResource::make(Book::query()->where('id', '=', $createdReadingLogEntry->book_id)->first())->resolve();

        return Inertia::flash([
            'toast' => [
                'type' => 'success',
                'message' => $book['title'].' added to reading log',
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

    //    public function destroy(ReadingLogEntry $readingLogEntry)
    //    {
    //        //
    //    }
}
