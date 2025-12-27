<?php

namespace Dharma\VideoUploader\Tests\Feature;

use Dharma\VideoUploader\Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_config_file_is_loaded()
    {
        $this->assertNotNull(config('video-uploader'));
    }
}
