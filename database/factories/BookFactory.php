<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'author' => $this->faker->name(),
            'isbn' => $this->faker->isbn13(),
            'publisher' => $this->faker->company(),
            'publication_year' => $this->faker->year(),
        ];
    }
}
