<?php

namespace App\Enums;

enum ReadingStatus: string
{
    case WANT_TO_READ = 'want_to_read';
    case READING = 'reading';
    case FINISHED = 'finished';
    case ABANDONED = 'abandoned';
}
