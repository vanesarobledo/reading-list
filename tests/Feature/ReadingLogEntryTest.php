<?php

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\ReadingLogEntry;
use App\Models\User;

use function Pest\Laravel\patch;

const STARTED_AT = '2026-08-01';
const FINISHED_AT = '2026-10-31';
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
            ->from(route('dashboard'))
            ->post(route('reading.log.store'), $payload);

        $response->assertValid()->assertRedirectBack();
        $response->assertInertiaFlash('toast');
        $response->assertInertiaFlash('toast.type', 'success');
        $response->assertInertiaFlash('toast.message', $book->title.' added to reading log');
        $this->assertDatabaseHas((new ReadingLogEntry)->getTable(), $readingLog);
    });

    test('user can add duplicate entries to their reading log', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();
        $status = ReadingStatus::WANT_TO_READ->value;

        $response1 = $this->actingAs($user)
            ->post(route('reading.log.store'), [
                'book_id' => $book->id,
                'status' => $status,
            ]);

        $response1->assertValid();

        $response2 = $this->actingAs($user)
            ->post(route('reading.log.store'), [
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
            ->post(route('reading.log.store'), $payload);

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
            ->post(route('reading.log.store'), $payload);

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
        $readingLogEntry = ReadingLogEntry::factory()->for($user)->for($book)->create();

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->delete(route('reading.log.destroy', $readingLogEntry));

        $response->assertRedirectBack();
        $response->assertInertiaFlash('toast');
        $response->assertInertiaFlash('toast.type', 'success');
        $response->assertInertiaFlash('toast.message', $book->title.' removed from reading log');
        $this->assertModelMissing($readingLogEntry);
    });

    test('user cannot delete another user\'s reading log entry', function () {
        $user = User::factory()->create();

        $anotherUser = User::factory()->create();
        $anotherBook = Book::factory()->create();
        $anotherReadingLogEntry = ReadingLogEntry::factory()->for($anotherUser)->for($anotherBook)->create();

        $response = $this->actingAs($user)
            ->delete(route('reading.log.destroy', $anotherReadingLogEntry->id));

        $response->assertForbidden();
        $this->assertModelExists($anotherReadingLogEntry);
    });

    test('guest cannot delete a reading log entry', function () {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingLogEntry = ReadingLogEntry::factory()->for($user)->for($book)->create();

        $response = $this->delete(route('reading.log.destroy', $readingLogEntry));

        $response->assertRedirect(route('login'));
        $this->assertModelExists($readingLogEntry);
    });

    test('returns 404 if reading log entry does not exist', function () {
        $user = User::factory()->create();
        $nonExistentReadingLogEntryId = 9999;

        $response = $this->actingAs($user)
            ->delete(route('reading.log.destroy', $nonExistentReadingLogEntryId));

        $response->assertNotFound();
    });

    test('user with duplicate reading log entries only deletes the individual reading log entry', function () {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readingLogEntry = ReadingLogEntry::factory()->for($user)->for($book)->create();
        $duplicateReadingLogEntry = ReadingLogEntry::factory()->for($user)->for($book)->create();

        $this->actingAs($user)
            ->delete(route('reading.log.destroy', $readingLogEntry->id));

        $this->assertModelMissing($readingLogEntry);
        $this->assertModelExists($duplicateReadingLogEntry);
    });

    test('user deleting an entry does not delete another user\'s entry for the same book', function () {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $book = Book::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($user)->for($book)->create();
        $readingLogEntryWithSameBookDifferentUser = ReadingLogEntry::factory()->for($anotherUser)->for($book)->create();

        $this->actingAs($user)
            ->delete(route('reading.log.destroy', $readingLogEntry->id));

        $this->assertModelMissing($readingLogEntry);
        $this->assertModelExists($readingLogEntryWithSameBookDifferentUser);
    });
});

describe('update a reading log entry', function () {
    test('user can update a reading log entry', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($user)->for($book)->create();
        $payload = [
            'status' => ReadingStatus::FINISHED->value,
            'started_at' => STARTED_AT,
            'finished_at' => FINISHED_AT,
        ];

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('reading.log.update', $readingLogEntry), $payload);

        $response->assertValid()->assertRedirectBack();
        $response->assertInertiaFlash('toast');
        $response->assertInertiaFlash('toast.type', 'success');
        $response->assertInertiaFlash('toast.message', $book->title.' updated in reading log');

        $readingLogEntry->refresh();

        expect($readingLogEntry->status->value)->toBe($payload['status'])
            ->and($readingLogEntry->started_at?->toDateString())->toBe($payload['started_at'])
            ->and($readingLogEntry->finished_at?->toDateString())->toBe($payload['finished_at']);
    });

    test('user can partially update a reading log entry', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($user)->for($book)->create([
            'started_at' => STARTED_AT,
            'finished_at' => FINISHED_AT,
        ]);
        $payload = [
            'status' => ReadingStatus::FINISHED->value,
        ];

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('reading.log.update', $readingLogEntry), $payload);

        $response->assertValid()->assertRedirectBack();

        $readingLogEntry->refresh();

        expect($readingLogEntry->status->value)->toBe($payload['status'])
            ->and($readingLogEntry->started_at?->toDateString())->toBe(STARTED_AT)
            ->and($readingLogEntry->finished_at?->toDateString())->toBe(FINISHED_AT);
    });

    test('user can clear a date in a reading log entry', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($user)->for($book)->create([
            'status' => ReadingStatus::FINISHED,
            'started_at' => STARTED_AT,
            'finished_at' => FINISHED_AT,
        ]);

        $payload = [
            'started_at' => '',
        ];

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('reading.log.update', $readingLogEntry), $payload);

        $response->assertValid()->assertRedirectBack();

        $readingLogEntry->refresh();

        expect($readingLogEntry->started_at?->toDateString())->toBeNull()
            ->and($readingLogEntry->finished_at?->toDateString())->toBe(FINISHED_AT)
            ->and($readingLogEntry->status)->toBe(ReadingStatus::FINISHED);
    });

    test('user cannot update another user\'s reading log entry', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($anotherUser)->for($book)->create();

        $payload = [
            'status' => ReadingStatus::FINISHED->value,
        ];

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('reading.log.update', $readingLogEntry), $payload);

        $response->assertForbidden();

        $readingLogEntry->refresh();

        expect($readingLogEntry->status->value)->not->toBe($payload['status']);
    });

    test('guest cannot update a user\'s reading log entry', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($user)->for($book)->create();

        $payload = [
            'status' => ReadingStatus::FINISHED->value,
        ];

        $response = patch(route('reading.log.update', $readingLogEntry), $payload);

        $response->assertRedirect(route('login'));

        $readingLogEntry->refresh();

        expect($readingLogEntry->status->value)->not->toBe($payload['status']);
    });

    test('returns 404 if reading log entry does not exist', function () {
        $user = User::factory()->create();
        $nonExistentReadingLogEntryId = 9999;

        $response = $this->actingAs($user)
            ->patch(route('reading.log.update', $nonExistentReadingLogEntryId));

        $response->assertNotFound();
    });

    test('user cannot change book_id or user_id of reading log entry', function () {
        $book = Book::factory()->create();
        $anotherBook = Book::factory()->create();
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($book)->for($user)->create();

        $payload = [
            'status' => ReadingStatus::FINISHED->value,
            'user_id' => $anotherUser->id,
            'book_id' => $anotherBook->id,
        ];

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('reading.log.update', $readingLogEntry), $payload);

        $response->assertValid()->assertRedirect(route('dashboard'));

        $readingLogEntry->refresh();

        expect($readingLogEntry->status)->toBe(ReadingStatus::FINISHED);
        expect($readingLogEntry->user())->not->toBe($anotherUser);
        expect($readingLogEntry->book_id)->not->toBe($anotherBook->id);
    });
});
