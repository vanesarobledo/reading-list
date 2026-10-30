<?php

namespace Database\Factories;

use App\Enums\ReadingStatus;
use App\Models\ReadingLogEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingLogEntry>
 */
class ReadingLogEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => ReadingStatus::WANT_TO_READ->value,
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
