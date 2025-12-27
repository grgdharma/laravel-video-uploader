<?php

namespace Dharma\VideoUploader\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Dharma\VideoUploader\Tests\TestCase;
use Dharma\VideoUploader\VideoUploader;
use Dharma\VideoUploader\Jobs\ProcessVideoJob; 

class UploadJobTest extends TestCase
{
    public function test_upload_job_is_dispatched()
    {
        Queue::fake();
        Storage::fake('local');

        // ✅ DEFINE FILE
        $file = UploadedFile::fake()->create('video.mp4',5000,'video/mp4');
        $uploader = app(VideoUploader::class);
        $uploader->uploadVideo($file);
        Queue::assertPushed(ProcessVideoJob::class);
    }
}