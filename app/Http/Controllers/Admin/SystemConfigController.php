<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;

class SystemConfigController extends Controller
{
    public function edit()
    {
        $this->authorize('viewAny', SystemConfig::class);

        $configs = SystemConfig::all()->keyBy('config_key');

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $configs,
            ]);
        }

        return view('admin.config.edit', compact('configs'));
    }

    public function update()
    {
        $this->authorize('update', SystemConfig::class);

        $validated = request()->validate([
            'minimum_balance' => ['required', 'integer', 'min:0'],
        ]);

        $config = SystemConfig::updateOrCreate(
            ['config_key' => 'minimum_balance'],
            ['config_value' => (string) $validated['minimum_balance']]
        );

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi berhasil disimpan.',
                'data' => $config,
            ]);
        }

        return redirect()->route('admin.config.edit')->with('success', 'Konfigurasi berhasil disimpan.');
    }
}
