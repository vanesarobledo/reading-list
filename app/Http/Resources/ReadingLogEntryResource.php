<?php

namespace App\Http\Resources;

use App\Models\Book;
use App\Models\ReadingLogEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Book
 *
 * @property ReadingLogEntry $pivot
 */
class ReadingLogEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->pivot->id,
            'book_id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'publisher' => $this->publisher,
            'publication_year' => $this->publication_year,
            'status' => $this->pivot->status,
            'status_label' => $this->pivot->status->label(),
            'started_at' => $this->pivot->started_at,
            'finished_at' => $this->pivot->finished_at,
        ];
    }
}
