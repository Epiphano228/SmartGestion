<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, fn () => static::where('key', $key)->value('value') ?? $default);
    }

    public static function setValue(string $key, mixed $value, string $type = 'string'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'type' => $type]);
        Cache::forget("setting.{$key}");
    }
    public static function setMany(array $values): void
    {
        $now = now();
        $rows = collect($values)->map(fn ($value, $key) => [
            'key' => $key,
            'value' => $value,
            'type' => 'string',
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all();

        static::upsert($rows, ['key'], ['value', 'type', 'updated_at']);
        foreach (array_keys($values) as $key) Cache::forget("setting.{$key}");
    }
}
