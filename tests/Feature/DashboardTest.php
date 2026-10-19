<?php

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\ReadingLogEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('authenticated users see their books in the dashboard', function () {
    $user = User::factory()->create();
    $books = Book::factory()->count(3)->create();
    $readingLog = ReadingLogEntry::factory()
        ->count(3)
        ->state(new Sequence(
            [
                'book_id' => $books[0]->id,
                'user_id' => $user->id,
                'status' => ReadingStatus::WANT_TO_READ->value,
            ],
            [
                'book_id' => $books[1]->id,
                'user_id' => $user->id,
                'status' => ReadingStatus::FINISHED->value,
            ],
            [
                'book_id' => $books[2]->id,
                'user_id' => $user->id,
                'status' => ReadingStatus::READING->value,
            ],
        )
        )
        ->create();

    $unrelatedUser = User::factory()->create();
    $unrelatedBooks = Book::factory()->create();
    ReadingLogEntry::factory()->create([
        'book_id' => $unrelatedBooks->id,
        'user_id' => $unrelatedUser->id,
        'status' => ReadingStatus::READING->value,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Dashboard')
        ->has('readingLogEntries', 3)
        ->where('readingLogEntries.0.status', $readingLog->first()->status)
        ->where('readingLogEntries.0.id', $readingLog[0]->id)
        ->where('readingLogEntries.0.book_id', $books[0]->id)
    );
});
