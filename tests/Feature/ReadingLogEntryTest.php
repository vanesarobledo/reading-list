<?php

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\ReadingLogEntry;
use App\Models\User;

test('user can add a book to their reading log entry', function () {
    $book = Book::factory()->create();
    $user = User::factory()->create();

    $readingLog = [
        'book_id' => $book->id,
        'user_id' => $user->id,
        'status' => ReadingStatus::WANT_TO_READ->value,
        'started_at' => now(),
    ];

    $payload = [
        'book_id' => $book->id,
        'status' => ReadingStatus::WANT_TO_READ->value,
        'started_at' => now(),
    ];

    $response = $this->actingAs($user)
        ->post('/reading-log', $payload);

    $response->assertRedirectBack();
    $response->assertInertiaFlash('toast');
    $response->assertInertiaFlash('toast.message', $book->title.' added to reading log');
    $this->assertDatabaseHas((new ReadingLogEntry)->getTable(), $readingLog);
});

test('user can add duplicate entries to their reading log', function () {
    $book = Book::factory()->create();
    $user = User::factory()->create();
    $status = ReadingStatus::WANT_TO_READ->value;

    $response1 = $this->actingAs($user)
        ->post('/reading-log', [
            'book_id' => $book->id,
            'status' => $status,
        ]);

    $response1->assertRedirectBack();

    $response2 = $this->actingAs($user)
        ->post('/reading-log', [
            'book_id' => $book->id,
            'status' => $status,
        ]);

    $response2->assertRedirectBack();
});

test('user can only add a real book id to their reading log entry', function () {
    $user = User::factory()->create();
    $invalidBook = [
        'book_id' => 9999,
        'status' => ReadingStatus::WANT_TO_READ->value,
    ];

    $response = $this->actingAs($user)
        ->post('/reading-log', $invalidBook);

    $response->assertRedirectBack();
    $this->assertDatabaseMissing((new ReadingLogEntry)->getTable(), $invalidBook);
});
