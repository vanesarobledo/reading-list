<?php

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\ReadingLogEntry;
use App\Models\User;

describe('add a reading log entry', function () {
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

        $response->assertValid()->assertRedirectBack();
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

        $response1->assertValid();

        $response2 = $this->actingAs($user)
            ->post('/reading-log', [
                'book_id' => $book->id,
                'status' => $status,
            ]);

        $response2->assertValid();

        $this->assertDatabaseCount((new ReadingLogEntry)->getTable(), 2);
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
});

describe('delete a reading log entry', function () {
    test('user can delete a reading log entry', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();
        $readingLogEntry = ReadingLogEntry::create([
            'book_id' => $book->id,
            'status' => ReadingStatus::WANT_TO_READ->value,
            'started_at' => null,
            'finished_at' => null,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete('/reading-log/'.$readingLogEntry->id);

        $response->assertValid()->assertRedirectBack();
        $response->assertInertiaFlash('toast');
        $response->assertInertiaFlash('toast.message', $book->title.' removed from reading log');
        $this->assertModelMissing($readingLogEntry);
    });

    test('user cannot delete another user\'s reading log entry', function () {
        $user = User::factory()->create();

        $anotherUser = User::factory()->create();
        $anotherBook = Book::factory()->create();
        $anotherReadingLogEntry = ReadingLogEntry::create([
            'book_id' => $anotherBook->id,
            'status' => ReadingStatus::WANT_TO_READ->value,
            'started_at' => null,
            'finished_at' => null,
            'user_id' => $anotherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->delete('/reading-log/'.$anotherReadingLogEntry->id);

        $response->assertStatus(403);
        $this->assertModelExists($anotherReadingLogEntry);
    });
    test('guest cannot delete a reading log entry', function () {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingLogEntry = ReadingLogEntry::create([
            'book_id' => $book->id,
            'status' => ReadingStatus::WANT_TO_READ->value,
            'started_at' => null,
            'finished_at' => null,
            'user_id' => $user->id,
        ]);

        $response = $this->delete('/reading-log/'.$readingLogEntry->id);

        $response->assertRedirect(route('login'));
        $this->assertModelExists($readingLogEntry);
    });
    test('returns 404 if reading log entry does not exist')->todo();
    test('user with duplicate reading log entries only deletes the individual reading log entry')->todo();
    test('user cannot delete a reading log entry with the same book belonging to another user')->todo();
});
