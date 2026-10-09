<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'publication_year' => $this->publication_year,
            'status' => $this->pivot->status,
            'started_at' => $this->pivot->started_at,
            'finished_at' => $this->pivot->finished_at,
        ];
    }
}
