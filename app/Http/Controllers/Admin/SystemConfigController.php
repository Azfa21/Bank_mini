<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SystemConfigController extends Controller
{
    public function edit(): View
    {
        $configs = SystemConfig::all()->keyBy('config_key');

        return view('admin.config.edit', compact('configs'));
    }

    public function update(): RedirectResponse
    {
        $validated = request()->validate([
            'minimum_balance' => ['required', 'integer', 'min:0'],
        ]);

        SystemConfig::updateOrCreate(
            ['config_key' => 'minimum_balance'],
            ['config_value' => (string) $validated['minimum_balance']]
        );

        return redirect()->route('admin.config.edit')->with('success', 'Konfigurasi berhasil disimpan.');
    }
}
