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
            'bank_name' => ['required', 'string', 'max:100'],
            'operational_hours' => ['nullable', 'string', 'max:100'],
            'minimum_balance' => ['required', 'integer', 'min:0'],
            'admin_fee' => ['required', 'integer', 'min:0'],
            'max_withdrawal_without_order' => ['required', 'integer', 'min:0'],
            'min_deposit_to_activate' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated as $key => $value) {
            SystemConfig::updateOrCreate(
                ['config_key' => $key],
                ['config_value' => (string) ($value ?? '')]
            );
        }

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi berhasil disimpan.',
                'data' => SystemConfig::all(),
            ]);
        }

        return redirect()->route('admin.config.edit')->with('success', 'Konfigurasi berhasil disimpan.');
    }
}
