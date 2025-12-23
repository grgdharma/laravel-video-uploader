<?php

namespace Dharma\VideoUploader;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Dharma\VideoUploader\Jobs\ProcessVideoJob;
use Dharma\VideoUploader\Models\Video;

class VideoUploader
{
    protected string $disk;
    protected string $path;
    protected int $maxSize;

    public function __construct()
    {
        $this->disk    = config('video-uploader.disk', 'public');
        $this->path    = config('video-uploader.path', 'videos');
        $this->maxSize = config('video-uploader.max_size');
    }

    /**
     * Upload and queue video for processing
     */
    public function uploadVideo(UploadedFile $file, array $data = []): Video
    {
        $this->validateFile($file);

        $filePath = $this->storeFile($file);
        $video = $this->createVideoRecord($file, $filePath, $data);

        ProcessVideoJob::dispatch($video->id, $filePath, $this->disk);
        
        return $video;
    }

    /**
     * List videos with optional filters and pagination
     *
     * @param array $filters ['status' => string]
     * @param int   $perPage Number of items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Video::query();
        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        // Optional: order by latest uploaded
        $query->orderBy('created_at', 'desc');
        return $query->paginate($perPage);
    }
    /**
     * Get a single video by ID
     *
     * @param int $videoId
     * @return Video
     *
     * @throws ModelNotFoundException
     */
    public function getById(int $videoId): Video
    {
        return Video::findOrFail($videoId);
    }

    /**
     * Delete a video by its ID along with files
     *
     * @param int $videoId
     * @return bool
     *
     * @throws ModelNotFoundException
     */
    public function deleteById(int $videoId): bool
    {
        $video = Video::findOrFail($videoId);

        // Delete original video file
        if ($video->original_path && Storage::disk($video->disk)->exists($video->original_path)) {
            Storage::disk($video->disk)->delete($video->original_path);
        }

        // Delete processed files folder (HLS or converted versions)
        if ($video->processed_path) {
            $folder = dirname($video->processed_path);
            if (Storage::disk($video->disk)->exists($folder)) {
                Storage::disk($video->disk)->deleteDirectory($folder);
            }
        }
        // Delete DB record
        return $video->delete();
    }

    protected function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > $this->maxSize) {
            throw new Exception('Video exceeds maximum allowed size.');
        }
    }

    protected function storeFile(UploadedFile $file): string
    {
        $filename = sprintf(
            '%s_%s',
            now()->timestamp,
            str_replace(' ', '_', $file->getClientOriginalName())
        );

        return $file->storeAs($this->path, $filename, $this->disk);
    }
    protected function createVideoRecord( UploadedFile $file, string $filePath, array $data): Video 
    {
        return Video::create([
            'user_id'       => $data['user_id'] ?? null,
            'title'         => $data['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original_path' => $filePath,
            'disk'          => 'public',
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'status'        => Video::STATUS_UPLOADED,
            'progress'      => 0,
        ]);
    }
}