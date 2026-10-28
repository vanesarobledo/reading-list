<?php

namespace App\Models;

use App\Enums\ReadingStatus;
use App\Policies\ReadingLogEntryPolicy;
use Database\Factories\ReadingLogEntryFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(ReadingLogEntryPolicy::class)]
class ReadingLogEntry extends Model
{
    /** @use HasFactory<ReadingLogEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => ReadingStatus::class,
        'started_at' => 'date',
        'finished_at' => 'date',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
