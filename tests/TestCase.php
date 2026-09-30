<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Views usam @vite; sem build gerado o teste quebraria com
        // ViteManifestNotFoundException. Não testamos o bundle aqui.
        $this->withoutVite();
    }
}
