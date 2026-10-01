<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value', 'type'])]
class Setting extends Model
{
    public const CACHE_KEY = 'settings.all';

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * Retrieve a setting value, falling back to the given default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = static::cached();

        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        return static::castValue($settings[$key]['value'], $settings[$key]['type']);
    }

    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => static::serializeValue($value, $type), 'type' => $type],
        );
    }

    /**
     * @return array<string, array{value: ?string, type: string}>
     */
    public static function cached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return static::query()
                ->get(['key', 'value', 'type'])
                ->mapWithKeys(fn (self $setting): array => [
                    $setting->key => ['value' => $setting->value, 'type' => $setting->type],
                ])
                ->all();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            'integer', 'int' => (int) $value,
            'float', 'decimal' => (float) $value,
            'array', 'json' => json_decode($value, true),
            default => $value,
        };
    }

    protected static function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'array', 'json' => json_encode($value),
            'boolean', 'bool' => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
