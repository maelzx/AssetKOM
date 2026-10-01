<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class AssetTagGenerator
{
    public const SEQUENCE_KEY = 'asset_tag_sequence';

    /**
     * Generate the next unique asset tag using the configured prefix.
     *
     * Runs inside a transaction with a row lock so concurrent creates cannot
     * hand out the same tag. Manually entered tags are skipped.
     */
    public function generate(): string
    {
        return DB::transaction(function (): string {
            $prefix = (string) Setting::get('asset_tag_prefix', 'AST');

            $setting = Setting::query()
                ->where('key', self::SEQUENCE_KEY)
                ->lockForUpdate()
                ->first();

            $next = $setting ? ((int) $setting->value) + 1 : 1;

            do {
                $tag = sprintf('%s-%04d', $prefix, $next);
                $next++;
            } while (Asset::withTrashed()->where('asset_tag', $tag)->exists());

            $sequence = $next - 1;

            if ($setting) {
                $setting->update(['value' => (string) $sequence, 'type' => 'integer']);
            } else {
                Setting::create([
                    'key' => self::SEQUENCE_KEY,
                    'value' => (string) $sequence,
                    'type' => 'integer',
                ]);
            }

            Setting::flushCache();

            return $tag;
        });
    }
}
