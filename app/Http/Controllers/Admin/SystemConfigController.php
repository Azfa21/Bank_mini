<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;

class SystemConfigController extends Controller
{
    public function edit()
    {
        $configs = SystemConfig::all()->keyBy('config_key');

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $configs,
            ]);
        }

        return view('admin.config.edit', compact('configs'));
    }

    public function update()
    {
        $validated = request()->validate([
            'minimum_balance' => ['required', 'integer', 'min:0'],
        ]);

        $config = SystemConfig::updateOrCreate(
            ['config_key' => 'minimum_balance'],
            ['config_value' => (string) $validated['minimum_balance']]
        );

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi berhasil disimpan.',
                'data' => $config,
            ]);
        }

        return redirect()->route('admin.config.edit')->with('success', 'Konfigurasi berhasil disimpan.');
    }
}
