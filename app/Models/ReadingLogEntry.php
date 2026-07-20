<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ReadingLogEntry extends Pivot
{
    public $incrementing = true;

    protected $table = 'book_user';

    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => Status::class,
        'started_at' => 'date',
        'finished_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
