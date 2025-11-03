<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gallery;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class GalleryController extends Controller
{
    public function index(){
        $user = Auth::user();
        
        $galleries = Gallery::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        return Inertia::render('Dashboard/index', [
            'galleries' => $galleries,
        ]);
    } 
    
    //store gallery
    // public function store(Request $request)
    // {
    //     // Add debug logging at start
    //     \Log::info('Gallery creation started', [
    //         'inputs' => $request->all(),
    //         'files' => [
    //             'images' => $request->hasFile('images') ? count($request->file('images')) : 0,
    //             'videos' => $request->hasFile('videos') ? count($request->file('videos')) : 0
    //         ]
    //     ]);

    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'category' => 'required|string|max:255',
    //         'images' => 'required|array|max:40',
    //         'images.*' => [
    //             'required',
    //             'image',
    //             'mimes:jpeg,png,jpg,gif',
    //             'max:30720', // 30MB
    //         ],
    //         'videos' => 'sometimes|array|max:40',
    //         'videos.*' => [
    //             'sometimes',
    //             'mimetypes:video/mp4,video/quicktime,video/ogg,video/mov',
    //             'max:204800', // 200MB
    //         ],
    //         'videoLinks' => 'nullable|string',
    //     ]);

    //     // Count files and enforce 40 file limit
    //     $imageCount = count($request->file('images'));
    //     $videoCount = $request->hasFile('videos') ? count($request->file('videos')) : 0;
        
    //     if (($imageCount + $videoCount) > 40) {
    //         \Log::warning('File limit exceeded', ['total' => $imageCount + $videoCount]);
    //         return back()->with('error', 'Maximum 40 files allowed (images + videos combined)');
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $userId = Auth::id();
            
    //         // Create gallery first to get ID
    //         $gallery = Gallery::create([
    //             'name' => htmlspecialchars($validated['name']),
    //             'category' => htmlspecialchars($validated['category']),
    //             'user_id' => $userId,
    //             'video_links' => [],
    //             'images' => [],
    //             'videos' => [],
    //         ]);

    //         \Log::info('Gallery record created', ['gallery_id' => $gallery->id]);

    //         // Create directory structure: public/uploads/user_id/gallery_id
    //         $galleryDir = public_path("uploads/{$userId}/{$gallery->id}");
    //         \Log::info('Creating gallery directory', ['path' => $galleryDir]);

    //         if (!File::exists($galleryDir)) {
    //             $created = File::makeDirectory($galleryDir, 0755, true);
    //             \Log::info('Directory creation result', [
    //                 'success' => $created, 
    //                 'exists' => File::exists($galleryDir),
    //                 'writable' => is_writable($galleryDir)
    //             ]);
    //         }

    //         $imagePaths = [];
    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $index => $image) {
    //                 $filename = uniqid().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
    //                 $destination = "{$galleryDir}/{$filename}";
                    
    //                 try {
    //                     $image->move($galleryDir, $filename);
    //                     \Log::info("Image {$index} saved", [
    //                         'filename' => $filename,
    //                         'path' => $destination,
    //                         'size' => File::size($destination)
    //                     ]);
    //                     $imagePaths[] = "/uploads/{$userId}/{$gallery->id}/{$filename}";
    //                 } catch (\Exception $e) {
    //                     \Log::error("Failed to save image {$index}", [
    //                         'error' => $e->getMessage(),
    //                         'file' => $filename
    //                     ]);
    //                     throw $e;
    //                 }
    //             }
    //         }

    //         $videoPaths = [];
    //         if ($request->hasFile('videos')) {
    //             foreach ($request->file('videos') as $index => $video) {
    //                 $filename = uniqid().'_'.Str::random(10).'.'.$video->getClientOriginalExtension();
    //                 $destination = "{$galleryDir}/{$filename}";
                    
    //                 try {
    //                     $video->move($galleryDir, $filename);
    //                     \Log::info("Video {$index} saved", [
    //                         'filename' => $filename,
    //                         'path' => $destination,
    //                         'size' => File::size($destination)
    //                     ]);
    //                     $videoPaths[] = "/uploads/{$userId}/{$gallery->id}/{$filename}";
    //                 } catch (\Exception $e) {
    //                     \Log::error("Failed to save video {$index}", [
    //                         'error' => $e->getMessage(),
    //                         'file' => $filename
    //                     ]);
    //                     throw $e;
    //                 }
    //             }
    //         }

    //         // Process video links
    //         $videoLinks = [];
    //         if ($request->filled('videoLinks')) {
    //             $videoLinks = collect(explode(',', $request->input('videoLinks', '')))
    //                 ->map(fn($link) => trim($link))
    //                 ->filter(fn($link) => !empty($link) && filter_var($link, FILTER_VALIDATE_URL))
    //                 ->take(20)
    //                 ->toArray();
    //         }

    //         // Update gallery with file paths
    //         $gallery->update([
    //             'video_links' => $videoLinks,
    //             'images' => $imagePaths,
    //             'videos' => $videoPaths,
    //         ]);

    //         \Log::info('Gallery updated with media paths', [
    //             'image_count' => count($imagePaths),
    //             'video_count' => count($videoPaths)
    //         ]);

    //         DB::commit();
            
    //         \Log::info('Gallery creation completed successfully');
    //         return redirect()->route('dashboard')->with('success', 'Gallery created successfully!');
            
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::error('Gallery creation failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         return back()->with('error', 'Failed to create gallery: '.$e->getMessage());
    //     }
    // }
    public function store(Request $request)
    {
        // Add debug logging at start
        \Log::info('Gallery creation started', [
            'inputs' => $request->all(),
            'files' => [
                'images' => $request->hasFile('images') ? count($request->file('images')) : 0,
                'videos' => $request->hasFile('videos') ? count($request->file('videos')) : 0
            ]
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'images' => 'required|array|max:40',
            'images.*' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:30720', // 30MB
            ],
            'videos' => 'sometimes|array|max:40',
            'videos.*' => [
                'sometimes',
                'mimetypes:video/mp4,video/quicktime,video/ogg,video/mov',
                'max:204800', // 200MB
            ],
            'videoLinks' => 'nullable|string',
        ]);

        // Count files and enforce 40 file limit
        $imageCount = count($request->file('images'));
        $videoCount = $request->hasFile('videos') ? count($request->file('videos')) : 0;
        
        if (($imageCount + $videoCount) > 40) {
            \Log::warning('File limit exceeded', ['total' => $imageCount + $videoCount]);
            return back()->with('error', 'Maximum 40 files allowed (images + videos combined)');
        }

        DB::beginTransaction();

        try {
            $userId = Auth::id();
            
            // Create gallery first to get ID
            $gallery = Gallery::create([
                'name' => htmlspecialchars($validated['name']),
                'category' => htmlspecialchars($validated['category']),
                'user_id' => $userId,
                'video_links' => [],
                'images' => [],
                'videos' => [],
            ]);

            \Log::info('Gallery record created', ['gallery_id' => $gallery->id]);

            // Create directory structure in public_html/uploads/user_id/gallery_id
            $galleryDir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/{$userId}/{$gallery->id}";
            \Log::info('Creating gallery directory', ['path' => $galleryDir]);

            if (!File::exists($galleryDir)) {
                $created = File::makeDirectory($galleryDir, 0755, true);
                \Log::info('Directory creation result', [
                    'success' => $created, 
                    'exists' => File::exists($galleryDir),
                    'writable' => is_writable($galleryDir)
                ]);
            }

            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $filename = uniqid().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
                    $destination = "{$galleryDir}/{$filename}";
                    
                    try {
                        $image->move($galleryDir, $filename);
                        \Log::info("Image {$index} saved", [
                            'filename' => $filename,
                            'path' => $destination,
                            'size' => File::size($destination)
                        ]);
                        $imagePaths[] = "/uploads/{$userId}/{$gallery->id}/{$filename}";
                    } catch (\Exception $e) {
                        \Log::error("Failed to save image {$index}", [
                            'error' => $e->getMessage(),
                            'file' => $filename
                        ]);
                        throw $e;
                    }
                }
            }

            $videoPaths = [];
            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $index => $video) {
                    $filename = uniqid().'_'.Str::random(10).'.'.$video->getClientOriginalExtension();
                    $destination = "{$galleryDir}/{$filename}";
                    
                    try {
                        $video->move($galleryDir, $filename);
                        \Log::info("Video {$index} saved", [
                            'filename' => $filename,
                            'path' => $destination,
                            'size' => File::size($destination)
                        ]);
                        $videoPaths[] = "/uploads/{$userId}/{$gallery->id}/{$filename}";
                    } catch (\Exception $e) {
                        \Log::error("Failed to save video {$index}", [
                            'error' => $e->getMessage(),
                            'file' => $filename
                        ]);
                        throw $e;
                    }
                }
            }

            // Process video links
            $videoLinks = [];
            if ($request->filled('videoLinks')) {
                $videoLinks = collect(explode(',', $request->input('videoLinks', '')))
                    ->map(fn($link) => trim($link))
                    ->filter(fn($link) => !empty($link) && filter_var($link, FILTER_VALIDATE_URL))
                    ->take(20)
                    ->toArray();
            }

            // Update gallery with file paths
            $gallery->update([
                'video_links' => $videoLinks,
                'images' => $imagePaths,
                'videos' => $videoPaths,
            ]);

            \Log::info('Gallery updated with media paths', [
                'image_count' => count($imagePaths),
                'video_count' => count($videoPaths),
                'directory' => $galleryDir
            ]);

            DB::commit();
            
            \Log::info('Gallery creation completed successfully');
            return redirect()->route('dashboard')->with('success', 'Gallery created successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gallery creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to create gallery: '.$e->getMessage());
        }
    }

    public function setCover(Request $request, Gallery $gallery)
    {
        $validated = $request->validate([
            'cover_image' => [
                'required',
                'string',
                Rule::in($gallery->images)
            ]
        ]);

        try {
            $gallery->update(['cover_image' => $validated['cover_image']]);
            
            return back()->with([
                'success' => 'Cover image updated successfully',
                'gallery' => $gallery->fresh()
            ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to update cover image: ' . $e->getMessage()
            ]);
        }
    }

    public function updateCoverPosition(Request $request, $id)
    {
        $request->validate([
            'cover_image_position' => 'required|numeric|min:0|max:100'
        ]);

        try {
            $gallery = Gallery::findOrFail($id);
            
            // Make sure the user owns this gallery or has permission
            if ($gallery->user_id !== auth()->id()) {
                abort(403, 'Unauthorized');
            }

            $gallery->update([
                'cover_image_position' => $request->cover_image_position
            ]);

            // Return back to the same page with success message
            return back()->with('success', 'Cover image position updated successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update cover image position']);
        }
    }
               

    public function show($id) {
        $gallery = Gallery::findOrFail($id);
        $shareableLink = \App\Models\ShareableLink::where('gallery_id', $gallery->id)->first();
        
        return Inertia::render('Dashboard/singleGallery', [
            'gallery' => [
                ...$gallery->toArray(),
                'token' => $gallery->token,
                'videoLinks' => $gallery->video_links,
                'cover_image' => $gallery->cover_image,
                'cover_image_position' => $gallery->cover_image_position
            ],
            'shareLink' => $shareableLink ? url("/shared/{$shareableLink->token}") : null,
            'download_enabled' => $shareableLink ? $shareableLink->download_enabled : false,
        ]);
    }


    //Delete Media
    public function deleteMedia(Request $request, Gallery $gallery)
    {
        $mediaType = $request->input('media_type'); // image, video, or video_link
        $mediaPath = $request->input('media_path'); // the path or link to delete

        // Ensure only the owner can delete the media
        if ($gallery->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $isDeletingCoverImage = ($mediaType === 'image' && $gallery->cover_image === $mediaPath);

        // Handle deletion based on media type
        if ($mediaType === 'image') {
            $gallery->images = array_values(array_filter($gallery->images ?? [], fn($img) => $img !== $mediaPath));
            $file = public_path($mediaPath);
            if (file_exists($file)) {
                unlink($file);
            }
        } elseif ($mediaType === 'video') {
            $gallery->videos = array_values(array_filter($gallery->videos ?? [], fn($vid) => $vid !== $mediaPath));
            $file = public_path($mediaPath);
            if (file_exists($file)) {
                unlink($file);
            }
        } elseif ($mediaType === 'video_link') {
            $gallery->video_links = array_values(array_filter($gallery->video_links ?? [], fn($link) => $link !== $mediaPath));
        } else {
            return back()->with('error', 'Invalid media type');
        }

        if ($isDeletingCoverImage) {
            $gallery->cover_image = null;
        }

        // Save updated gallery
        $gallery->save();

        return back()->with([
            'success' => 'Media deleted successfully',
            'gallery' => $gallery,
        ]);
    }

    public function destroy(Request $request, Gallery $gallery)
    {
        // Ensure only the owner can delete the gallery
        if ($gallery->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Delete all associated files
        foreach ($gallery->images ?? [] as $image) {
            $file = public_path($image);
            if (file_exists($file)) {
                unlink($file);
            }
        }

        foreach ($gallery->videos ?? [] as $video) {
            $file = public_path($video);
            if (file_exists($file)) {
                unlink($file);
            }
        }

        // Delete the gallery record
        $gallery->delete();

        // Return to the same page with updated galleries
        return redirect()->back()->with([
            'success' => 'Gallery deleted successfully!',
            'galleries' => Gallery::where('user_id', auth()->id())->get()
        ]);
    } 

    //Gallery Edit
    public function edit(Gallery $gallery)
    {
        if ($gallery->user_id !== auth()->id()) {
            abort(403);
        }

        $galleries = Gallery::where('user_id', auth()->id())->latest()->get();

        return Inertia::render('Dashboard/index', [
            'galleries' => $galleries,
            'editGallery' => [
                ...$gallery->toArray(),
                'videoLinks' => $gallery->video_links
            ],
        ]);
    }

    //Gallery Update
    // public function update(Request $request, Gallery $gallery)
    // {
    //     $userId = $gallery->user_id;
    //     $galleryId = $gallery->id;
    
    //     // New folder path: uploads/{userId}/{galleryId}
    //     $galleryDir = base_path("public/uploads/{$userId}/{$galleryId}");
    //     //$galleryDir = base_path("../uploads/{$userId}/{$galleryId}");
    //     //$galleryDir = base_path("../public_html/uploads/{$userId}/{$galleryId}");
    
    //     if (!File::exists($galleryDir)) {
    //         File::makeDirectory($galleryDir, 0755, true);
    //     }
    
    //     $newImages = [];
    //     if ($request->hasFile('images')) {
    //         foreach ($request->file('images') as $image) {
    //             $filename = uniqid().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
    //             $image->move($galleryDir, $filename);
    //             $newImages[] = "/uploads/{$userId}/{$galleryId}/{$filename}";
    //         }
    //     }
    
    //     $newVideos = [];
    //     if ($request->hasFile('videos')) {
    //         foreach ($request->file('videos') as $video) {
    //             $filename = uniqid().'_'.Str::random(10).'.'.$video->getClientOriginalExtension();
    //             $video->move($galleryDir, $filename);
    //             $newVideos[] = "/uploads/{$userId}/{$galleryId}/{$filename}";
    //         }
    //     }
    
    //     // Handle deletions
    //     $deletedImages = json_decode($request->input('deleted_images', '[]'), true);
    //     $deletedVideos = json_decode($request->input('deleted_videos', '[]'), true);
    
    //     foreach ($deletedImages as $imageUrl) {
    //         $file = public_path($imageUrl);
    //         if (file_exists($file)) {
    //             unlink($file);
    //         }
    //     }
    
    //     foreach ($deletedVideos as $videoUrl) {
    //         $file = public_path($videoUrl);
    //         if (file_exists($file)) {
    //             unlink($file);
    //         }
    //     }
    
    //     // Handle video links
    //     $videoLinks = [];
    //     if ($request->filled('videoLinks')) {
    //         $videoLinks = collect(explode(',', $request->input('videoLinks')))
    //             ->map(fn($link) => trim($link))
    //             ->filter(fn($link) => !empty($link) && filter_var($link, FILTER_VALIDATE_URL))
    //             ->unique()
    //             ->values()
    //             ->toArray();
    //     }
    
    //     // Update gallery
    //     $gallery->update([
    //         'name' => $request->name,
    //         'category' => $request->category,
    //         'video_links' => $videoLinks,
    //         'images' => array_merge(
    //             array_diff($gallery->images ?? [], $deletedImages),
    //             $newImages
    //         ),
    //         'videos' => array_merge(
    //             array_diff($gallery->videos ?? [], $deletedVideos),
    //             $newVideos
    //         ),
    //     ]);
    
    //     return redirect()->back()->with([
    //         'success' => 'Gallery updated successfully!',
    //         'gallery' => [
    //             ...$gallery->fresh()->toArray(),
    //             'video_links' => $videoLinks
    //         ]
    //     ]);
    // }
    // public function update(Request $request, Gallery $gallery)
    // {
    //     // Add debug logging at start
    //     \Log::info('Gallery update started', [
    //         'gallery_id' => $gallery->id,
    //         'inputs' => $request->all(),
    //         'files' => [
    //             'images' => $request->hasFile('images') ? count($request->file('images')) : 0,
    //             'videos' => $request->hasFile('videos') ? count($request->file('videos')) : 0
    //         ]
    //     ]);

    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'category' => 'required|string|max:255',
    //         'images' => 'sometimes|array|max:20',
    //         'images.*' => [
    //             'sometimes',
    //             'image',
    //             'mimes:jpeg,png,jpg,gif',
    //             'max:30720', // 30MB
    //         ],
    //         'videos' => 'sometimes|array|max:20',
    //         'videos.*' => [
    //             'sometimes',
    //             'mimetypes:video/mp4,video/quicktime,video/ogg,video/mov',
    //             'max:204800', // 200MB
    //         ],
    //         'videoLinks' => 'nullable|string',
    //         'deleted_images' => 'nullable|string',
    //         'deleted_videos' => 'nullable|string',
    //     ]);

    //     $userId = $gallery->user_id;
    //     $galleryId = $gallery->id;
    //     $galleryDir = public_path("uploads/{$userId}/{$galleryId}");

    //     // Count existing media
    //     $currentImageCount = count($gallery->images ?? []);
    //     $currentVideoCount = count($gallery->videos ?? []);

    //     // Count new uploads
    //     $newImageCount = $request->hasFile('images') ? count($request->file('images')) : 0;
    //     $newVideoCount = $request->hasFile('videos') ? count($request->file('videos')) : 0;

    //     // Count deletions
    //     $deletedImages = json_decode($request->input('deleted_images', '[]'), true) ?? [];
    //     $deletedVideos = json_decode($request->input('deleted_videos', '[]'), true) ?? [];

    //     // Calculate final counts
    //     $finalImageCount = $currentImageCount - count($deletedImages) + $newImageCount;
    //     $finalVideoCount = $currentVideoCount - count($deletedVideos) + $newVideoCount;

    //     // Enforce 40 file limit (20 images + 20 videos)
    //     if ($finalImageCount > 20 || $finalVideoCount > 20) {
    //         \Log::warning('File limit exceeded', [
    //             'images' => $finalImageCount,
    //             'videos' => $finalVideoCount
    //         ]);
    //         return back()->with('error', 'Maximum 20 images and 20 videos allowed');
    //     }

    //     DB::beginTransaction();

    //     try {
    //         \Log::info('Creating/verifying gallery directory', ['path' => $galleryDir]);
    //         if (!File::exists($galleryDir)) {
    //             $created = File::makeDirectory($galleryDir, 0755, true);
    //             \Log::info('Directory creation result', [
    //                 'success' => $created,
    //                 'writable' => is_writable($galleryDir)
    //             ]);
    //         }

    //         $newImages = [];
    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $index => $image) {
    //                 $filename = uniqid().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
    //                 $destination = "{$galleryDir}/{$filename}";
                    
    //                 try {
    //                     $image->move($galleryDir, $filename);
    //                     \Log::info("New image saved", [
    //                         'index' => $index,
    //                         'filename' => $filename,
    //                         'size' => File::size($destination)
    //                     ]);
    //                     $newImages[] = "/uploads/{$userId}/{$galleryId}/{$filename}";
    //                 } catch (\Exception $e) {
    //                     \Log::error("Failed to save image", [
    //                         'error' => $e->getMessage(),
    //                         'file' => $filename
    //                     ]);
    //                     throw $e;
    //                 }
    //             }
    //         }

    //         $newVideos = [];
    //         if ($request->hasFile('videos')) {
    //             foreach ($request->file('videos') as $index => $video) {
    //                 $filename = uniqid().'_'.Str::random(10).'.'.$video->getClientOriginalExtension();
    //                 $destination = "{$galleryDir}/{$filename}";
                    
    //                 try {
    //                     $video->move($galleryDir, $filename);
    //                     \Log::info("New video saved", [
    //                         'index' => $index,
    //                         'filename' => $filename,
    //                         'size' => File::size($destination)
    //                     ]);
    //                     $newVideos[] = "/uploads/{$userId}/{$galleryId}/{$filename}";
    //                 } catch (\Exception $e) {
    //                     \Log::error("Failed to save video", [
    //                         'error' => $e->getMessage(),
    //                         'file' => $filename
    //                     ]);
    //                     throw $e;
    //                 }
    //             }
    //         }

    //         // Process deletions with logging
    //         \Log::info('Processing deletions', [
    //             'images' => count($deletedImages),
    //             'videos' => count($deletedVideos)
    //         ]);
            
    //         foreach ($deletedImages as $imageUrl) {
    //             $file = public_path($imageUrl);
    //             if (File::exists($file)) {
    //                 try {
    //                     File::delete($file);
    //                     \Log::info("Deleted image", ['path' => $imageUrl]);
    //                 } catch (\Exception $e) {
    //                     \Log::error("Failed to delete image", [
    //                         'path' => $imageUrl,
    //                         'error' => $e->getMessage()
    //                     ]);
    //                 }
    //             }
    //         }

    //         foreach ($deletedVideos as $videoUrl) {
    //             $file = public_path($videoUrl);
    //             if (File::exists($file)) {
    //                 try {
    //                     File::delete($file);
    //                     \Log::info("Deleted video", ['path' => $videoUrl]);
    //                 } catch (\Exception $e) {
    //                     \Log::error("Failed to delete video", [
    //                         'path' => $videoUrl,
    //                         'error' => $e->getMessage()
    //                     ]);
    //                 }
    //             }
    //         }

    //         // Process video links
    //         $videoLinks = [];
    //         if ($request->filled('videoLinks')) {
    //             $videoLinks = collect(explode(',', $request->input('videoLinks')))
    //                 ->map(fn($link) => trim($link))
    //                 ->filter(fn($link) => !empty($link) && filter_var($link, FILTER_VALIDATE_URL))
    //                 ->take(20)
    //                 ->toArray();
    //             \Log::info('Processed video links', ['count' => count($videoLinks)]);
    //         }

    //         // Prepare updated media arrays
    //         $updatedImages = array_values(array_diff($gallery->images ?? [], $deletedImages));
    //         $updatedVideos = array_values(array_diff($gallery->videos ?? [], $deletedVideos));

    //         if (count($newImages)) {
    //             $updatedImages = array_merge($updatedImages, $newImages);
    //         }

    //         if (count($newVideos)) {
    //             $updatedVideos = array_merge($updatedVideos, $newVideos);
    //         }

    //         \Log::info('Updating gallery record', [
    //             'image_count' => count($updatedImages),
    //             'video_count' => count($updatedVideos),
    //             'video_link_count' => count($videoLinks)
    //         ]);

    //         // Update gallery
    //         $gallery->update([
    //             'name' => htmlspecialchars($validated['name']),
    //             'category' => htmlspecialchars($validated['category']),
    //             'video_links' => $videoLinks,
    //             'images' => $updatedImages,
    //             'videos' => $updatedVideos,
    //         ]);

    //         DB::commit();
            
    //         \Log::info('Gallery update completed successfully');
    //         return redirect()->back()->with([
    //             'success' => 'Gallery updated successfully!',
    //             'gallery' => $gallery->fresh()
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::error('Gallery update failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
            
    //         // Clean up any newly uploaded files if transaction failed
    //         $cleanupErrors = [];
    //         foreach ($newImages as $imagePath) {
    //             $file = public_path($imagePath);
    //             if (File::exists($file)) {
    //                 try {
    //                     File::delete($file);
    //                 \Log::info("Cleaned up new image after failure", ['path' => $imagePath]);
    //                 } catch (\Exception $cleanupError) {
    //                     $cleanupErrors[] = $cleanupError->getMessage();
    //                 }
    //             }
    //         }
            
    //         foreach ($newVideos as $videoPath) {
    //             $file = public_path($videoPath);
    //             if (File::exists($file)) {
    //                 try {
    //                     File::delete($file);
    //                     \Log::info("Cleaned up new video after failure", ['path' => $videoPath]);
    //                 } catch (\Exception $cleanupError) {
    //                     $cleanupErrors[] = $cleanupError->getMessage();
    //                 }
    //             }
    //         }

    //         if (count($cleanupErrors)) {
    //             \Log::error('Cleanup errors', ['errors' => $cleanupErrors]);
    //         }

    //         return back()->with('error', 'Failed to update gallery: '.$e->getMessage());
    //     }
    // }

    public function update(Request $request, Gallery $gallery)
{
    // Add debug logging at start
    \Log::info('Gallery update started', [
        'gallery_id' => $gallery->id,
        'user_id' => $gallery->user_id,
        'inputs' => $request->all(),
        'files' => [
            'images' => $request->hasFile('images') ? count($request->file('images')) : 0,
            'videos' => $request->hasFile('videos') ? count($request->file('videos')) : 0
        ]
    ]);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'images' => 'sometimes|array|max:20',
        'images.*' => [
            'sometimes',
            'image',
            'mimes:jpeg,png,jpg,gif',
            'max:30720', // 30MB
        ],
        'videos' => 'sometimes|array|max:20',
        'videos.*' => [
            'sometimes',
            'mimetypes:video/mp4,video/quicktime,video/ogg,video/mov',
            'max:204800', // 200MB
        ],
        'videoLinks' => 'nullable|string',
        'deleted_images' => 'nullable|string',
        'deleted_videos' => 'nullable|string',
    ]);

    $userId = $gallery->user_id;
    $galleryId = $gallery->id;
    
    // Use the same directory structure as store method
    //$galleryDir = public_path("uploads/{$userId}/{$galleryId}");
    $galleryDir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/{$userId}/{$galleryId}";
    
    \Log::info('Gallery directory path', [
        'path' => $galleryDir,
        'exists' => File::exists($galleryDir),
        'writable' => File::exists($galleryDir) ? is_writable($galleryDir) : 'N/A'
    ]);

    // Count existing media
    $currentImageCount = count($gallery->images ?? []);
    $currentVideoCount = count($gallery->videos ?? []);

    // Count new uploads
    $newImageCount = $request->hasFile('images') ? count($request->file('images')) : 0;
    $newVideoCount = $request->hasFile('videos') ? count($request->file('videos')) : 0;

    // Count deletions
    $deletedImages = json_decode($request->input('deleted_images', '[]'), true) ?? [];
    $deletedVideos = json_decode($request->input('deleted_videos', '[]'), true) ?? [];

    // Calculate final counts
    $finalImageCount = $currentImageCount - count($deletedImages) + $newImageCount;
    $finalVideoCount = $currentVideoCount - count($deletedVideos) + $newVideoCount;

    // Enforce 40 file limit (20 images + 20 videos)
    if ($finalImageCount > 20 || $finalVideoCount > 20) {
        \Log::warning('File limit exceeded', [
            'images' => $finalImageCount,
            'videos' => $finalVideoCount
        ]);
        return back()->with('error', 'Maximum 20 images and 20 videos allowed');
    }

    DB::beginTransaction();

    try {
        // Create/verify gallery directory (same as store method)
        \Log::info('Creating/verifying gallery directory', ['path' => $galleryDir]);
        if (!File::exists($galleryDir)) {
            $created = File::makeDirectory($galleryDir, 0755, true);
            \Log::info('Directory creation result', [
                'success' => $created,
                'exists' => File::exists($galleryDir),
                'writable' => is_writable($galleryDir)
            ]);
        }

        // Handle new image uploads
        $newImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                // Use same naming convention as store method
                $filename = uniqid().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
                $destination = "{$galleryDir}/{$filename}";
                
                try {
                    $image->move($galleryDir, $filename);
                    \Log::info("New image saved", [
                        'index' => $index,
                        'filename' => $filename,
                        'destination' => $destination,
                        'size' => File::size($destination)
                    ]);
                    // Use same path format as store method
                    $newImages[] = "/uploads/{$userId}/{$galleryId}/{$filename}";
                } catch (\Exception $e) {
                    \Log::error("Failed to save image", [
                        'error' => $e->getMessage(),
                        'file' => $filename,
                        'destination' => $destination
                    ]);
                    throw $e;
                }
            }
        }

        // Handle new video uploads
        $newVideos = [];
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $index => $video) {
                // Use same naming convention as store method
                $filename = uniqid().'_'.Str::random(10).'.'.$video->getClientOriginalExtension();
                $destination = "{$galleryDir}/{$filename}";
                
                try {
                    $video->move($galleryDir, $filename);
                    \Log::info("New video saved", [
                        'index' => $index,
                        'filename' => $filename,
                        'destination' => $destination,
                        'size' => File::size($destination)
                    ]);
                    // Use same path format as store method
                    $newVideos[] = "/uploads/{$userId}/{$galleryId}/{$filename}";
                } catch (\Exception $e) {
                    \Log::error("Failed to save video", [
                        'error' => $e->getMessage(),
                        'file' => $filename,
                        'destination' => $destination
                    ]);
                    throw $e;
                }
            }
        }

        // Process deletions with logging
        \Log::info('Processing deletions', [
            'images' => count($deletedImages),
            'videos' => count($deletedVideos)
        ]);
        
        foreach ($deletedImages as $imageUrl) {
            $file = public_path($imageUrl);
            if (File::exists($file)) {
                try {
                    File::delete($file);
                    \Log::info("Deleted image", ['path' => $imageUrl]);
                } catch (\Exception $e) {
                    \Log::error("Failed to delete image", [
                        'path' => $imageUrl,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                \Log::warning("Image file not found for deletion", ['path' => $imageUrl]);
            }
        }

        foreach ($deletedVideos as $videoUrl) {
            $file = public_path($videoUrl);
            if (File::exists($file)) {
                try {
                    File::delete($file);
                    \Log::info("Deleted video", ['path' => $videoUrl]);
                } catch (\Exception $e) {
                    \Log::error("Failed to delete video", [
                        'path' => $videoUrl,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                \Log::warning("Video file not found for deletion", ['path' => $videoUrl]);
            }
        }

        // Process video links (same as store method)
        $videoLinks = [];
        if ($request->filled('videoLinks')) {
            $videoLinks = collect(explode(',', $request->input('videoLinks')))
                ->map(fn($link) => trim($link))
                ->filter(fn($link) => !empty($link) && filter_var($link, FILTER_VALIDATE_URL))
                ->take(20)
                ->toArray();
            \Log::info('Processed video links', ['count' => count($videoLinks)]);
        }

        // Prepare updated media arrays
        $updatedImages = array_values(array_diff($gallery->images ?? [], $deletedImages));
        $updatedVideos = array_values(array_diff($gallery->videos ?? [], $deletedVideos));

        // Add new files to existing arrays
        if (count($newImages)) {
            $updatedImages = array_merge($updatedImages, $newImages);
        }

        if (count($newVideos)) {
            $updatedVideos = array_merge($updatedVideos, $newVideos);
        }

        \Log::info('Final media counts', [
            'existing_images' => count($gallery->images ?? []),
            'deleted_images' => count($deletedImages),
            'new_images' => count($newImages),
            'final_images' => count($updatedImages),
            'existing_videos' => count($gallery->videos ?? []),
            'deleted_videos' => count($deletedVideos),
            'new_videos' => count($newVideos),
            'final_videos' => count($updatedVideos),
            'video_links' => count($videoLinks)
        ]);

        // Update gallery
        $gallery->update([
            'name' => htmlspecialchars($validated['name']),
            'category' => htmlspecialchars($validated['category']),
            'video_links' => $videoLinks,
            'images' => $updatedImages,
            'videos' => $updatedVideos,
        ]);

        DB::commit();
        
        \Log::info('Gallery update completed successfully', [
            'gallery_id' => $gallery->id,
            'final_directory' => $galleryDir
        ]);
        
        return redirect()->back()->with([
            'success' => 'Gallery updated successfully!',
            'gallery' => $gallery->fresh()
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Gallery update failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Clean up any newly uploaded files if transaction failed
        $cleanupErrors = [];
        foreach ($newImages as $imagePath) {
            $file = public_path($imagePath);
            if (File::exists($file)) {
                try {
                    File::delete($file);
                    \Log::info("Cleaned up new image after failure", ['path' => $imagePath]);
                } catch (\Exception $cleanupError) {
                    $cleanupErrors[] = $cleanupError->getMessage();
                }
            }
        }
        
        foreach ($newVideos as $videoPath) {
            $file = public_path($videoPath);
            if (File::exists($file)) {
                try {
                    File::delete($file);
                    \Log::info("Cleaned up new video after failure", ['path' => $videoPath]);
                } catch (\Exception $cleanupError) {
                    $cleanupErrors[] = $cleanupError->getMessage();
                }
            }
        }

        if (count($cleanupErrors)) {
            \Log::error('Cleanup errors', ['errors' => $cleanupErrors]);
        }

        return back()->with('error', 'Failed to update gallery: '.$e->getMessage());
    }
}
    

}