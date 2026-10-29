<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    protected $fillable = ['title', 'author', 'publisher', 'publication_year', 'isbn', 'external_source', 'external_id'];

    /**
     * @return HasMany<ReadingLogEntry, $this>
     */
    public function readingLogEntries(): HasMany
    {
        return $this->hasMany(ReadingLogEntry::class);
    }
}
