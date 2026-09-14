<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\CrmWebsite;

class CrmWebsiteSeeder extends Seeder
{
    public function run()
    {
        $sites = [
            ['EZ Custom Boxes', '#22c55e'],
            ['The Custom Boxes', '#2563eb'],
            ['My Box Printing', '#84cc16'],
            ['Blackline Packaging', '#475569'],
            ['Findheadsets', '#0f172a'],
            ['The Custom Boxes AU', '#0ea5e9'],
            ['The Custom Boxes UK', '#3b82f6'],
            ['Go Custom Boxes', '#9d174d'],
            ['EZ Coustom Boxes UK', '#16a34a'],
            ['My Box Packaging', '#f97316'],
            ['Premium Boxes', '#f59e0b'],
            ['Label Pouches', '#1e3a8a'],
            ['Kay Packaging', '#0f766e'],
            ['My Box Printing App', '#84cc16'],
            ['Headsetzone', '#eab308'],
            ['Ez Custom Boxes', '#10b981'],
            ['Go Custom Boxes', '#059669'],
        ];
        foreach ($sites as $s) {
            CrmWebsite::firstOrCreate(['name' => $s[0]], ['color' => $s[1], 'is_active' => true]);
        }
    }
}
