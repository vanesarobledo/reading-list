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
    $payload = [
        'book_id' => 9999,
        'status' => ReadingStatus::WANT_TO_READ->value,
    ];

    $response = $this->actingAs($user)
        ->post('/reading-log', $payload);

    $response->assertSessionHasErrors('book_id');
    $this->assertDatabaseCount((new ReadingLogEntry)->getTable(), 0);
});

test('rejects a reading log entry without a required field', function (string $field) {
    $book = Book::factory()->create();
    $user = User::factory()->create();

    $payload = [
        'book_id' => $book->id,
        'status' => ReadingStatus::WANT_TO_READ->value,
    ];
    unset($payload[$field]);

    $response = $this->actingAs($user)
        ->post('/reading-log', $payload);

    $response->assertSessionHasErrors($field);
    $this->assertDatabaseCount((new ReadingLogEntry)->getTable(), 0);
})->with([
    'missing book id' => ['book_id'],
    'missing status' => ['status'],
]);
