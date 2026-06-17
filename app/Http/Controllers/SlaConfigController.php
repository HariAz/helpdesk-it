<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SlaConfig;
use Illuminate\Http\Request;

class SlaConfigController extends Controller
{
    public function index()
    {
        $globalConfigs = SlaConfig::whereNull('category_id')->orderBy('priority')->get();
        $categoryConfigs = SlaConfig::whereNotNull('category_id')
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('priority')
            ->get();
        $categories = Category::whereNull('parent_id')->where('is_active', true)->get();

        return view('settings.sla-config', compact('globalConfigs', 'categoryConfigs', 'categories'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'configs'                         => 'required|array',
            'configs.*.priority'              => 'required|in:kritis,tinggi,sedang,rendah',
            'configs.*.category_id'           => 'nullable|exists:categories,id',
            'configs.*.response_time_hours'   => 'required|numeric|min:0.5|max:720',
            'configs.*.resolution_time_hours' => 'required|numeric|min:0.5|max:720',
        ]);

        foreach ($data['configs'] as $cfg) {
            SlaConfig::updateOrCreate(
                ['priority' => $cfg['priority'], 'category_id' => $cfg['category_id'] ?: null],
                [
                    'response_time_hours'   => $cfg['response_time_hours'],
                    'resolution_time_hours' => $cfg['resolution_time_hours'],
                ]
            );
        }

        return back()->with('success', 'Konfigurasi SLA diperbarui.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'priority'              => 'required|in:kritis,tinggi,sedang,rendah',
            'category_id'           => 'required|exists:categories,id',
            'response_time_hours'   => 'required|numeric|min:0.5|max:720',
            'resolution_time_hours' => 'required|numeric|min:0.5|max:720',
        ]);

        SlaConfig::updateOrCreate(
            ['priority' => $data['priority'], 'category_id' => $data['category_id']],
            [
                'response_time_hours'   => $data['response_time_hours'],
                'resolution_time_hours' => $data['resolution_time_hours'],
            ]
        );

        return back()->with('success', 'Override SLA per kategori ditambahkan.');
    }

    public function destroy(SlaConfig $slaConfig)
    {
        if (!$slaConfig->category_id) {
            return back()->with('error', 'SLA global tidak bisa dihapus.');
        }
        $slaConfig->delete();
        return back()->with('success', 'Override SLA dihapus.');
    }
}
