<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait RespondsWithJson
{
    protected function wantsJson(?Request $request = null): bool
    {
        $request ??= request();

        return $request->expectsJson() || $request->is('api/*');
    }
}
