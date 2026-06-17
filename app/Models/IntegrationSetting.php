<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IntegrationSetting extends Model
{
    protected $fillable = ['key', 'value', 'description', 'is_encrypted'];

    protected $casts = ['is_encrypted' => 'boolean'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::where('key', $key)->first();
        if (!$row) return $default;
        if ($row->is_encrypted && $row->value) {
            try { return Crypt::decryptString($row->value); } catch (\Throwable) { return $default; }
        }
        return $row->value;
    }

    public static function set(string $key, mixed $value, bool $encrypt = false): void
    {
        static::updateOrCreate(['key' => $key], [
            'value'        => $encrypt ? Crypt::encryptString((string)$value) : (string)$value,
            'is_encrypted' => $encrypt,
        ]);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $val = static::get($key);
        return $val === null ? $default : filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    public static function getMany(array $keys): array
    {
        $rows = static::whereIn('key', $keys)->get()->keyBy('key');
        $result = [];
        foreach ($keys as $k) {
            $row = $rows[$k] ?? null;
            if (!$row) { $result[$k] = null; continue; }
            if ($row->is_encrypted && $row->value) {
                try { $result[$k] = Crypt::decryptString($row->value); } catch (\Throwable) { $result[$k] = null; }
            } else {
                $result[$k] = $row->value;
            }
        }
        return $result;
    }
}
