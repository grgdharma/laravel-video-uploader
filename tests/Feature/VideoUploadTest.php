<?php

namespace Dharma\VideoUploader\Tests\Feature;

use Dharma\VideoUploader\VideoUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Dharma\VideoUploader\Tests\TestCase;

class VideoUploadTest extends TestCase
{
    public function test_it_uploads_video_successfully()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('video.mp4',5000,'video/mp4');
        $uploader = app(VideoUploader::class);
        $video = $uploader->uploadVideo($file);
        Storage::disk('public')->assertExists($video->original_path);
        $this->assertSame('uploaded', $video->status);
    }

}