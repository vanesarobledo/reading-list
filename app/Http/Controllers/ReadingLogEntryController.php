<?php

namespace App\Http\Controllers;

use App\Enums\ReadingStatus;
use App\Models\ReadingLogEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReadingLogEntryController extends Controller
{
    //    public function index()
    //    {
    //        //
    //    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'book_id' => 'required',
            'status' => [Rule::enum(ReadingStatus::class)],
            'started_at' => 'nullable|date',
            'finished_at' => 'nullable|date',
        ]);

        ReadingLogEntry::create($validated);

        return redirect()->back();
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
