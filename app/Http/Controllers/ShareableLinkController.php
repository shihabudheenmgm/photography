<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\ShareableLink;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShareableLinkController extends Controller
{
    // Generate a shareable link
    public function generate(Request $request, $id)
    {
        $gallery = Gallery::findOrFail($id);

        // Check if shareable link already exists
        $existingLink = ShareableLink::where('gallery_id', $gallery->id)->first();
        if ($existingLink) {
            return response()->json([
                'shareableLink' => url("/shared/{$existingLink->token}"),
                'download_enabled' => $existingLink->download_enabled
            ]);
        }

        // Generate a new token and link
        $token = Str::random(32);

        $link = ShareableLink::create([
            'gallery_id' => $gallery->id,
            'token' => $token,
            'restrictions' => [
                'watermark' => true,
                'video_limit' => 180,
            ],
            'download_enabled' => false,
        ]);

        return response()->json([
            'shareableLink' => url("/shared/{$link->token}"),
            'download_enabled' => $link->download_enabled
        ]);
    }


    public function getLink($id)
    {
        $link = ShareableLink::where('gallery_id', $id)->first();
        if ($link) {
            return response()->json([
                'shareableLink' => url("/shared/{$link->token}"),
                'download_enabled' => $link->download_enabled
            ]);
        }

        return response()->json(['shareableLink' => null, 'download_enabled' => false], 200);
    }


    // Show the shared gallery
    public function show($token)
    {
        $link = ShareableLink::with(['gallery.user'])->where('token', $token)->firstOrFail();
    
        $gallery = $link->gallery;

        // Get the logo URL from the associated user
        $logoUrl = $gallery->user->logo 
            ? $this->getLogoUrl($gallery->user->logo)
            : null;

        return Inertia::render('share/restrictedGallery', [
            'gallery' => [
                'name' => $gallery->name,
                'images' => $gallery->images,
                'videos' => $gallery->videos,
                'video_links' => $gallery->video_links,
                'token' => $link->token,
                'logo' => $logoUrl,
                'cover_image' => $gallery->cover_image,
                'cover_image_position' => $gallery->cover_image_position
            ],
            'restrictions' => [
                'watermark' => $link->restrictions['watermark'] ?? true,
                'video_limit' => $link->restrictions['video_limit'] ?? 180,
                'enable_download' => $link->download_enabled,
            ],
            'brandColor' => $gallery->user->brand_color ?? '#0178ed',
            'user' => [
                'profile_image' => $gallery->user->profile_image,
                'first_name' => $gallery->user->first_name,
                'last_name' => $gallery->user->last_name,
            ]
        ]);

    }

    private function getLogoUrl($logoPath)
    {
        if (filter_var($logoPath, FILTER_VALIDATE_URL)) {
            return $logoPath;
        }

        $cleanPath = ltrim($logoPath, '/');
        
        return url($cleanPath);
    }

    //enable download
    public function enableDownload($id)
    {
        $link = ShareableLink::where('gallery_id', $id)->firstOrFail();
        if (auth()->user()->id !== $link->gallery->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $link->download_enabled = true;
        $link->save();

        return response()->json(['message' => 'Download enabled successfully.']);
    }

    //download
    public function download($token)
    {
        // Find the ShareableLink by token
        $link = ShareableLink::where('token', $token)->firstOrFail();
        $gallery = $link->gallery;

        // Check if download is enabled
        if (!$link->download_enabled) {
            return response()->json(['message' => 'Download is not enabled for this gallery.'], 403);
        }

        // Create the zip file path
        $zip = new ZipArchive;
        $zipFileName = "gallery-{$gallery->id}.zip";
        $zipPath = storage_path("app/public/zips/{$zipFileName}");

        // Ensure zips directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $hasFiles = false;

        // Open ZIP archive
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            // Add images
            foreach ($gallery->images as $filePath) {
                $relativePath = ltrim($filePath, '/');
                //$localPath = base_path("../public_html/{$relativePath}");
                $localPath = public_path($relativePath);

                if (file_exists($localPath)) {
                    $zip->addFile($localPath, 'images/' . basename($localPath));
                    $hasFiles = true;
                } else {
                    \Log::warning("Image file not found: {$localPath}");
                }
            }

            // Add videos
            foreach ($gallery->videos as $filePath) {
                $relativePath = ltrim($filePath, '/');
                //$localPath = base_path("../public_html/{$relativePath}");
                $localPath = public_path($relativePath);

                if (file_exists($localPath)) {
                    $zip->addFile($localPath, 'videos/' . basename($localPath));
                    $hasFiles = true;
                } else {
                    \Log::warning("Video file not found: {$localPath}");
                }
            }

            $zip->close();
        }

        // No files were added
        if (!$hasFiles) {
            return response()->json(['message' => 'No files found to download.'], 404);
        }

        // Return download response and delete after sending
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }


}
