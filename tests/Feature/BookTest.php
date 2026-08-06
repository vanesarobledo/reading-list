<?php

use App\Models\User;
use Illuminate\Http\Response;

describe('BookController', function () {
    test('it can store a book', function () {
        $payload = [
            'title' => 'One Hundred Years of Solitude',
            'author' => 'Gabriel García Márquez',
            'isbn' => '9780380015030',
            'publication_year' => 1967];

        $response = $this->actingAs(User::factory()->create())
            ->post('/book', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('books', $payload);
    });
});
