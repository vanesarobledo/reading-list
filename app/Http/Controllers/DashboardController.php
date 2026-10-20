<?php

namespace App\Http\Controllers;

use App\Enums\ReadingStatus;
use App\Http\Resources\ReadingLogEntryResource;
use App\Models\ReadingLogEntry;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $readingLog = ReadingLogEntry::query()->with('book')->where('user_id', $user->id)->get();
        $readingLogEntries = ReadingLogEntryResource::collection($readingLog)->resolve();

        return Inertia::render('Dashboard', [
            'readingLogEntries' => $readingLogEntries,
            'readingLogEntryStatuses' => ReadingStatus::options(),
        ]);
    }
}
