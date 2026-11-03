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

        // Assert response is valid - book_id and user_id is simply ignored
        $response->assertValid()->assertRedirect(route('dashboard'));

        $readingLogEntry->refresh();

        expect($readingLogEntry->status)->toBe(ReadingStatus::FINISHED)
            ->and($readingLogEntry->user_id)->toBe($user->id)
            ->and($readingLogEntry->book_id)->toBe($book->id);
    });

    test('returns field validation error with invalid input', function ($payload, $field) {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($book)->for($user)->create([
            'started_at' => STARTED_AT,
            'finished_at' => FINISHED_AT,
        ]);

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('reading.log.update', $readingLogEntry), $payload);

        $response->assertInvalid([$field])->assertRedirect(route('dashboard'));

        $updatedReadingLogEntry = $readingLogEntry->fresh();

        expect($updatedReadingLogEntry->status)->toBe($readingLogEntry->status)
            ->and($updatedReadingLogEntry->started_at?->toDateString())->toBe($readingLogEntry->started_at?->toDateString())
            ->and($updatedReadingLogEntry->finished_at?->toDateString())->toBe($readingLogEntry->finished_at?->toDateString());
    })->with([
        'invalid started_at' => [['started_at' => 'not a date'], 'started_at'],
        'invalid finished_at' => [['finished_at' => 'not a date'], 'finished_at'],
        'invalid status' => [['status' => 'not a valid status'], 'status'],
        'null status' => [['status' => null], 'status'],
        'valid status and invalid started_at' => [[
            'status' => ReadingStatus::FINISHED->value,
            'started_at' => 'not a date',
        ], 'started_at'],
        'valid status and invalid finished_at' => [[
            'status' => ReadingStatus::FINISHED->value,
            'finished_at' => 'not a date',
        ], 'finished_at', ],
    ]);

    test('rejects request that does not contain valid keys to update', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($book)->for($user)->create();

        $payload = [];

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('reading.log.update', $readingLogEntry), $payload);

        $response->assertInvalid(['validation'])->assertRedirect(route('dashboard'));

        $updatedReadingLogEntry = $readingLogEntry->fresh();

        expect($updatedReadingLogEntry->status)->toBe($readingLogEntry->status)
            ->and($updatedReadingLogEntry->started_at)->toBe($readingLogEntry->started_at)
            ->and($updatedReadingLogEntry->finished_at)->toBe($readingLogEntry->finished_at);
    });

    test('user with duplicate reading log entries only updates the individual reading log entry', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($book)->for($user)->create();
        $duplicateReadingLogEntry = ReadingLogEntry::factory()->for($book)->for($user)->create();

        $payload = [
            'status' => ReadingStatus::FINISHED->value,
            'started_at' => STARTED_AT,
            'finished_at' => FINISHED_AT,
        ];

        $this->actingAs($user)
            ->patch(route('reading.log.update', $readingLogEntry), $payload);

        $readingLogEntry->refresh();
        $duplicateReadingLogEntry->refresh();

        expect($readingLogEntry->status)->toBe(ReadingStatus::FINISHED)
            ->and($readingLogEntry->started_at?->toDateString())->toBe(STARTED_AT)
            ->and($readingLogEntry->finished_at?->toDateString())->toBe(FINISHED_AT)
            ->and($duplicateReadingLogEntry->status)->toBe(ReadingStatus::WANT_TO_READ)
            ->and($duplicateReadingLogEntry->started_at?->toDateString())->not->toBe(STARTED_AT)
            ->and($duplicateReadingLogEntry->finished_at?->toDateString())->not->toBe(FINISHED_AT);
    });

    test('user with reading log entries with the same book can update an individual entry to the same status and keeps both entries', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($book)->for($user)->create([
            'status' => ReadingStatus::READING->value,
        ]);
        $duplicateReadingLogEntry = ReadingLogEntry::factory()->for($book)->for($user)->create([
            'status' => ReadingStatus::FINISHED->value,
        ]);

        $payload = [
            'status' => ReadingStatus::FINISHED->value,
        ];

        $this->actingAs($user)
            ->patch(route('reading.log.update', $readingLogEntry), $payload);

        $readingLogEntry->refresh();
        $duplicateReadingLogEntry->refresh();

        expect($readingLogEntry->status)->toBe(ReadingStatus::FINISHED)
            ->and($duplicateReadingLogEntry->status)->toBe(ReadingStatus::FINISHED);
    });

    test('user updating a reading log entry does not update the reading log entry for another user with the same book', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $readingLogEntry = ReadingLogEntry::factory()->for($book)->for($user)->create();
        $readingLogEntryWithSameBookDifferentUser = ReadingLogEntry::factory()->for($anotherUser)->for($book)->create();

        $payload = [
            'status' => ReadingStatus::FINISHED->value,
        ];

        $this->actingAs($user)
            ->patch(route('reading.log.update', $readingLogEntry), $payload);

        $readingLogEntry->refresh();
        $readingLogEntryWithSameBookDifferentUser->refresh();

        expect($readingLogEntry->status)->toBe(ReadingStatus::FINISHED)
            ->and($readingLogEntryWithSameBookDifferentUser->status)->toBe(ReadingStatus::WANT_TO_READ);
    });

});
