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
            ]);
        }

        // Generate a new token and link
        $token = Str::random(32);

        $link = ShareableLink::create([
            'gallery_id' => $gallery->id,
            'token' => $token,
            'restrictions' => [
                'watermark' => true,
                'video_limit' => 10,
            ],
            'download_enabled' => false,
        ]);

        return response()->json([
            'shareableLink' => url("/shared/{$link->token}"),
        ]);
    }

    public function getLink($id)
    {
        $link = ShareableLink::where('gallery_id', $id)->first();
        if ($link) {
            return response()->json(['shareableLink' => url("/shared/{$link->token}")]);
        }

        return response()->json(['shareableLink' => null], 404);
    }


    // Show the shared gallery
    public function show($token)
    {
        $link = ShareableLink::where('token', $token)->firstOrFail();
        $gallery = $link->gallery;

        return Inertia::render('share/restrictedGallery', [
            'gallery' => [
                'name' => $gallery->name,
                'images' => $gallery->images,
                'videos' => $gallery->videos,
                'video_links' => $gallery->video_links,
                'token' => $link->token,
            ],
            'restrictions' => [
                'watermark' => $link->restrictions['watermark'] ?? true,
                'video_limit' => $link->restrictions['video_limit'] ?? 180,
                'enable_download' => $link->download_enabled,
            ],
        ]);

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
    // public function download($token)
    // {
    //     // Find the ShareableLink by token
    //     $link = ShareableLink::where('token', $token)->firstOrFail();
    //     $gallery = $link->gallery;  // Access the gallery through the relationship

    //     // Check if the download is enabled
    //     if (!$link->download_enabled) {
    //         return response()->json(['message' => 'Download is not enabled for this gallery.'], 403);
    //     }

    //     // Create the zip file
    //     $zip = new ZipArchive;
    //     $zipFileName = "gallery-{$gallery->id}.zip";
    //     $zipPath = storage_path("app/public/zips/{$zipFileName}");

    //     // Create the directory if it doesn't exist
    //     if (!file_exists(dirname($zipPath))) {
    //         mkdir(dirname($zipPath), 0755, true);
    //     }

    //     // Add images and videos to the zip file
    //     if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    //         // Add images to the "images" folder in the zip
    //         foreach ($gallery->images as $filePath) {
    //             $relativePath = str_replace(Storage::url(''), '', $filePath);
    //             $localPath = Storage::disk('public')->path($relativePath);
    //             if (file_exists($localPath)) {
    //                 $zip->addFile($localPath, 'images/' . basename($localPath)); 
    //             }
    //         }

    //         // Add videos to the "videos" folder in the zip
    //         foreach ($gallery->videos as $filePath) {
    //             $relativePath = str_replace(Storage::url(''), '', $filePath);
    //             $localPath = Storage::disk('public')->path($relativePath);
    //             if (file_exists($localPath)) {
    //                 $zip->addFile($localPath, 'videos/' . basename($localPath));
    //             }
    //         }

    //         $zip->close();
    //     }

    //     // Return the download response and delete the file after sending
    //     return response()->download($zipPath)->deleteFileAfterSend(true);
    // }

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
                $relativePath = ltrim($filePath, '/'); // Remove leading slash
                $localPath = base_path("../public_html/{$relativePath}");

                if (file_exists($localPath)) {
                    $zip->addFile($localPath, 'images/' . basename($localPath));
                    $hasFiles = true;
                } else {
                    \Log::warning("Image file not found: {$localPath}");
                }
            }

            // Add videos
            foreach ($gallery->videos as $filePath) {
                $relativePath = ltrim($filePath, '/'); // Remove leading slash
                $localPath = base_path("../public_html/{$relativePath}");

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
