<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $guarded = [];
    protected $casts = ['properties' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function record(string $event, string $description, ?Model $subject = null, array $properties = []): void
    {
        static::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties,
            'ip_address' => request()?->ip(),
        ]);
    }
}
