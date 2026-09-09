<?php

use App\Models\Book;
use App\Models\User;

test('can store a book', function () {
    $payload = [
        'title' => 'One Hundred Years of Solitude',
        'author' => 'Gabriel García Márquez',
        'isbn' => '9780380015030',
        'publication_year' => 1967];

    $response = $this->actingAs(User::factory()->create())
        ->post('/book', $payload);

    $response->assertRedirectBack();
    $this->assertDatabaseHas('books', $payload);
});

test('rejects invalid input with validation errors', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post('/book', [
            'title' => '',
        ]);

    $response->assertSessionHasErrors('title');
    $this->assertDatabaseCount('books', 0);
});

test('returns a paginated json structure for the book list', function () {
    Book::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get('/book');

    $response->assertOk();
    $response->assertJsonStructure([
        'total',
        'per_page',
        'current_page',
        'last_page',
        'data' => ['*' => [
            'id',
            'title',
            'author',
        ]],
    ]);
});

test('limits the number of books returned to the page size', function () {
    $numBooks = 15;
    $expectPerPage = 10;

    Book::factory($numBooks)->create();

    $response = $this->actingAs(User::factory()->create())
        ->get('/book');

    $response->assertOk();
    $response->assertJsonCount($expectPerPage, 'data');
});

test('includes real book data in the paginated response', function () {
    $book = Book::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get('/book');

    $response->assertOk();
    $response->assertJsonFragment([
        'id' => $book->id,
        'title' => $book->title,
        'author' => $book->author,
    ]);
});

test('includes author data in the paginated response', function () {
    $author = 'Gabriel García Márquez';
    Book::factory()->create([
        'author' => $author,
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->get('/book?'.http_build_query([['search' => $author]]));

    $response->assertOk();
    $response->assertJsonFragment([
        'author' => $author,
    ]);
});

test('excludes non-matching books from search results', function () {
    $matchingTitle = 'One Hundred Years of Solitude';
    $nonMatchingTitle = 'Moby Dick';

    Book::factory()->create(
        ['title' => $matchingTitle]
    );

    Book::factory()->create(
        ['title' => $nonMatchingTitle]
    );

    $response = $this->actingAs(User::factory()->create())
        ->get('/book?'.http_build_query(['search' => $matchingTitle]));

    $response->assertOk();
    $response->assertJsonMissing([
        'title' => $nonMatchingTitle,
    ]);

});

test('returns an empty paginated response when there are no books', function () {
    $response = $this->actingAs(User::factory()->create())
        ->get('/book');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

test('reports the total book count separately from the page size', function () {
    $totalBookCount = 15;
    Book::factory($totalBookCount)->create();

    $response = $this->actingAs(User::factory()->create())
        ->get('/book');

    $response->assertOk();
    $response->assertJsonFragment([
        'total' => $totalBookCount,
    ]);
});
