<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsRepository
{
    private const CACHE_KEY = 'system_settings';

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::all()->mapWithKeys(fn (Setting $setting) => [
                $setting->key => $this->cast($setting->value, $setting->type),
            ])->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        Setting::where('key', $key)->update(['value' => $this->stringify($value)]);
        $this->flush();
    }

    public function setMany(array $keyValues): void
    {
        foreach ($keyValues as $key => $value) {
            Setting::where('key', $key)->update(['value' => $this->stringify($value)]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    private function cast(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => (bool) ((int) $value),
            'integer' => (int) $value,
            default => $value,
        };
    }
}
