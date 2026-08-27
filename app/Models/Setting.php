<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = ['key', 'value', 'encrypted', 'updated_by'];

    protected function casts(): array
    {
        return ['encrypted' => 'boolean'];
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        if (! $row) {
            return $default;
        }

        return $row->encrypted ? Crypt::decryptString($row->value) : $row->value;
    }

    public static function put(string $key, mixed $value, bool $encrypted = false, ?int $uid = null): void
    {
        if ($encrypted) {
            $value = Crypt::encryptString((string) $value);
        }

        static::query()->updateOrCreate(['key' => $key], [
            'value' => (string) $value,
            'encrypted' => $encrypted,
            'updated_by' => $uid,
        ]);
    }

    public static function allAsMap(): array
    {
        return static::query()->get()
            ->mapWithKeys(fn (Setting $row) => [$row->key => $row->encrypted ? Crypt::decryptString($row->value) : $row->value])
            ->all();
    }
}
