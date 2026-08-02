<?php

namespace Tests\Feature\Nasabah;

use Tests\Feature\Teller\TellerTestCase;

abstract class NasabahTestCase extends TellerTestCase
{
    protected function actingAsNasabah(): static
    {
        return $this->actingAs($this->nasabah, 'nasabah');
    }
}
