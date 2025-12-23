<?php

namespace Dharma\VideoUploader\Jobs;

use Exception;
use Dharma\VideoUploader\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Format\Video\X264;

class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $videoId;
    public string $videoPath;
    public string $disk;

    public $timeout = 0;          // Let FFmpeg run without timeout
    public $tries   = 3;          // Retry on failure
    public $backoff = [60, 300];  // Progressive retry

    public function __construct(int $videoId, string $videoPath, string $disk = 'public')
    {
        $this->videoId      = $videoId;
        $this->videoPath    = $videoPath;
        $this->disk         = $disk;
    }

    public function handle(): void
    {
        $video = Video::findOrFail($this->videoId);
        try {
            $this->markProcessing($video);

            $outputFolder = $this->prepareOutputFolder();

            $this->convertToHls($outputFolder);

            $this->markCompleted($video, $outputFolder);

        } catch (Exception $e) {
            $this->markFailed($video, $e);
            throw $e; // allow queue retry
        }
    }

    protected function markProcessing(Video $video): void
    {
        $video->update([
            'status'   => Video::STATUS_PROCESSING,
            'progress' => 10,
        ]);
    }

    protected function prepareOutputFolder(): string
    {
        $folder = 'videos/hls/' . pathinfo($this->videoPath, PATHINFO_FILENAME);

        Storage::disk($this->disk)->makeDirectory($folder);

        return $folder;
    }
    protected function convertToHls(string $outputFolder): void
    {
        FFMpeg::fromDisk('local')
            ->open($this->videoPath)
            ->exportForHLS()
            ->toDisk($this->disk)
            ->addFormat(
                (new X264)->setKiloBitrate(1500),
                fn ($media) => $media->scale(1280, 720)
            )
            ->addFormat(
                (new X264)->setKiloBitrate(800),
                fn ($media) => $media->scale(854, 480)
            )
            ->addFormat(
                (new X264)->setKiloBitrate(400),
                fn ($media) => $media->scale(640, 360)
            )
            ->save($outputFolder . '/playlist.m3u8');
    }
    protected function markCompleted(Video $video, string $outputFolder): void
    {
        $video->update([
            'processed_path' => $outputFolder . '/playlist.m3u8',
            'status'         => Video::STATUS_COMPLETED,
            'progress'       => 100,
        ]);
    }

    protected function markFailed(Video $video, Exception $exception): void
    {
        Log::error('Video processing failed', [
            'video_id' => $video->id,
            'error'    => $exception->getMessage(),
        ]);

        $video->update([
            'status' => Video::STATUS_FAILED,
        ]);
    }
}