<?php

namespace App\Http\Resources;

use App\Models\ReadingLogEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReadingLogEntry
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
            'id' => $this->id,
            'book' => BookResource::make($this->book)->resolve(),
            'status' => $this->status,
            'status_label' => $this->status->label(),
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
        ];
    }
}
