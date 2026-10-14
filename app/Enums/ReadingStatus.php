<?php

namespace App\Enums;

enum ReadingStatus: string
{
    case WANT_TO_READ = 'want_to_read';
    case READING = 'reading';
    case FINISHED = 'finished';
    case ABANDONED = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::WANT_TO_READ => 'want to read',
            self::READING => 'reading',
            self::FINISHED => 'finished',
            self::ABANDONED => 'abandoned',
        };
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
