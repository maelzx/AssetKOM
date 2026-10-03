<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Seed the default application settings.
     */
    public function run(): void
    {
        $defaults = [
            'org_name' => ['AssetKOM', 'string'],
            'default_currency' => [Currency::MYR->value, 'string'],
            'base_currency' => [Currency::MYR->value, 'string'],
            'usd_to_myr_rate' => [4.70, 'float'],
            'asset_tag_prefix' => ['AST', 'string'],
            'asset_tag_sequence' => [0, 'integer'],
            'depreciation_method' => ['straight_line', 'string'],
            'depreciation_rate' => [20, 'float'],
            'label_qr_enabled' => [false, 'boolean'],
        ];

        foreach ($defaults as $key => [$value, $type]) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value, 'type' => $type],
            );
        }

        Setting::flushCache();
    }
}
