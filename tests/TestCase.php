<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    private int $initialOutputBufferLevel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initialOutputBufferLevel = ob_get_level();

        config([
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);

        foreach ([
            storage_path('app/private'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/testing'),
            storage_path('framework/views'),
        ] as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->initialOutputBufferLevel) {
            ob_end_clean();
        }

        parent::tearDown();
    }
}
