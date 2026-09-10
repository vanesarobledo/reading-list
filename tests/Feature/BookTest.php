<?php

use App\Http\Controllers\BookController;
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

test('returns a paginated json structure for the book list', closure: function () {
    Book::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get('/book');

    $response->assertOk();
    $response->assertJsonStructure(structure: [
        'meta' => [
            'total',
            'per_page',
            'current_page',
            'last_page',
        ],
        'data' => ['*' => [
            'id',
            'title',
            'author',
        ]],
    ]);
});

test('limits the number of books returned to number per page sent', function () {
    $numBooks = 25;
    $expectPerPage = 20;

    Book::factory($numBooks)->create();

    $response = $this->actingAs(User::factory()->create())
        ->get('/book?'.http_build_query(['per_page' => $expectPerPage]));

    $response->assertOk();
    $response->assertJsonCount($expectPerPage, 'data');
});

test('returns max number of results when requested per page is greater than max', function () {
    $greaterThanMax = 100;
    Book::factory($greaterThanMax)->create();

    $response = $this->actingAs(User::factory()->create())
        ->get('/book?'.http_build_query(['per_page' => $greaterThanMax]));

    $response->assertOk();
    $response->assertJsonCount(BookController::MAX_PER_PAGE, 'data');
});

test('returns default number per page when per page query string is a non-positive int', function (mixed $nonPositiveIntPerPage) {
    Book::factory(BookController::DEFAULT_PER_PAGE)->create();

    $response = $this->actingAs(User::factory()->create())
        ->get('/book?'.http_build_query([['per_page' => $nonPositiveIntPerPage]]));

    $response->assertOk();
    $response->assertJsonCount(BookController::DEFAULT_PER_PAGE, 'data');
})->with(['abc', 0, -10]);

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

test('includes multiple matching results with query', function () {
    $matchingBook1 = Book::factory()->create(
        [
            'title' => 'One Hundred Years of Solitude',
        ]
    );

    $matchingBook2 = Book::factory()->create(
        [
            'title' => 'One Punch Man',
        ]
    );

    $nonMatchingBook = Book::factory()->create(
        [
            'title' => 'Moby Dick',
        ]
    );

    $query = 'one';

    $response = $this->actingAs(User::factory()->create())
        ->get('/book?'.http_build_query(['search' => $query]));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonFragment([
        'title' => $matchingBook1->title,
    ]);
    $response->assertJsonFragment([
        'title' => $matchingBook2->title,
    ]);
    $response->assertJsonMissing([
        'title' => $nonMatchingBook->title,
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
        ->get('/book?'.http_build_query(['search' => 'one']));

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
    $totalBookCount = 20;
    $expectPerPage = 15;

    Book::factory($totalBookCount)->create();

    $response = $this->actingAs(User::factory()->create())
        ->get('/book?'.http_build_query([['per_page' => $expectPerPage]]));

    $response->assertOk();
    $response->assertJsonFragment([
        'total' => $totalBookCount,
    ]);
});
