<?php

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
