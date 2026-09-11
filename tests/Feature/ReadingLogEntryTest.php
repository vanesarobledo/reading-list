<?php

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\ReadingLogEntry;
use App\Models\User;

test('user can add a book to their reading log entry', function () {
    $book = Book::factory()->create();
    $user = User::factory()->create();
    $payload = [
        'user_id' => $user->id,
        'book_id' => $book->id,
        'status' => ReadingStatus::WANT_TO_READ->value,
        'started_at' => now(),
    ];

    $response = $this->actingAs($user)
        ->post('/reading-log', $payload);

    $response->assertRedirectBack();
    $this->assertDatabaseHas((new ReadingLogEntry)->getTable(), $payload);
});
