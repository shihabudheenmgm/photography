<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gallery;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GalleryController extends Controller
{
    public function index(){
        $galleries = Gallery::latest()->get();
        return Inertia::render('Dashboard/index', [
            'galleries' => $galleries,
        ]);
    } 
    
    //store gallery
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB
            'videos.*' => 'mimetypes:video/mp4,video/quicktime,video/ogg,video/mov|max:204800', //200MB
            'videoLinks' => 'nullable|string',
        ]);

        // DB::beginTransaction();
        // try {
        //     $userId = Auth::id();
        //     //$userDir = public_path('uploads/' . $userId);
        //     $userDir = base_path('../uploads/' . $userId);

        //     if (!File::exists($userDir)) {
        //         File::makeDirectory($userDir, 0755, true);
        //     }
            
        //     $imagePaths = [];
        //     $videoPaths = [];

        //     // Process images
        //     if ($request->hasFile('images')) {
        //         foreach ($request->file('images') as $image) {
        //             $filename = uniqid().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
        //             $image->move($userDir, $filename);
        //             $imagePaths[] = "/uploads/{$userId}/{$filename}";
        //         }
        //     }

        //     // Process videos
        //     if ($request->hasFile('videos')) {
        //         foreach ($request->file('videos') as $video) {
        //             $filename = uniqid().'_'.Str::random(10).'.'.$video->getClientOriginalExtension();
        //             $video->move($userDir, $filename);
        //             $videoPaths[] = "/uploads/{$userId}/{$filename}";
        //         }
        //     }

        //     // Process video links
        //     $videoLinks = array_filter(
        //         array_map('trim', explode(',', $request->input('videoLinks', ''))),
        //         fn($link) => !empty($link) && filter_var($link, FILTER_VALIDATE_URL)
        //     );

        //     // Create gallery
        //     Gallery::create([
        //         'name' => htmlspecialchars($validated['name']),
        //         'category' => htmlspecialchars($validated['category']),
        //         'user_id' => $userId,
        //         'video_links' => $videoLinks,
        //         'images' => $imagePaths,
        //         'videos' => $videoPaths,
        //     ]);

        //     DB::commit();
        //     return redirect()->route('dashboard')->with('success', 'Gallery added successfully!');
            
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     // Cleanup uploaded files if error occurred
        //     foreach (array_merge($imagePaths, $videoPaths) as $path) {
        //         $file = public_path($path);
        //         if (file_exists($file)) {
        //             unlink($file);
        //         }
        //     }
        //     return back()->with('error', 'Failed to create gallery: '.$e->getMessage());
        // }
        
        DB::beginTransaction();

        try {
            $userId = Auth::id();
        
            // Step 1: Create the gallery first (without images/videos)
            $gallery = Gallery::create([
                'name' => htmlspecialchars($validated['name']),
                'category' => htmlspecialchars($validated['category']),
                'user_id' => $userId,
                'video_links' => [],
                'images' => [],
                'videos' => [],
            ]);
        
            // Step 2: Build directory path based on user & gallery
            $galleryDir = base_path("../public_html/uploads/{$userId}/{$gallery->id}");
        
            if (!File::exists($galleryDir)) {
                File::makeDirectory($galleryDir, 0755, true);
            }
        
            $imagePaths = [];
            $videoPaths = [];
        
            // Step 3: Store images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $filename = uniqid().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
                    $image->move($galleryDir, $filename);
                    $imagePaths[] = "/uploads/{$userId}/{$gallery->id}/{$filename}";
                }
            }
        
            // Step 4: Store videos
            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $video) {
                    $filename = uniqid().'_'.Str::random(10).'.'.$video->getClientOriginalExtension();
                    $video->move($galleryDir, $filename);
                    $videoPaths[] = "/uploads/{$userId}/{$gallery->id}/{$filename}";
                }
            }
        
            // Step 5: Process video links
            $videoLinks = array_filter(
                array_map('trim', explode(',', $request->input('videoLinks', ''))),
                fn($link) => !empty($link) && filter_var($link, FILTER_VALIDATE_URL)
            );
        
            // Step 6: Update gallery with file paths
            $gallery->update([
                'video_links' => $videoLinks,
                'images' => $imagePaths,
                'videos' => $videoPaths,
            ]);
        
            DB::commit();
            return redirect()->route('dashboard')->with('success', 'Gallery added successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create gallery: '.$e->getMessage());
        }
    }
               

    public function show($id){
        $gallery = Gallery::findOrFail($id);
        return Inertia::render('Dashboard/singleGallery', [
            'gallery' => [
                ...$gallery->toArray(),
                'token' => $gallery->token,
                'videoLinks' => $gallery->video_links
            ],
            'shareLink' => $gallery->shareable_link,
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
    //     //$userDir = public_path('uploads/' . $userId);
    //     $userDir = base_path('../public_html/uploads/' . $userId);

    //     if (!File::exists($userDir)) {
    //         File::makeDirectory($userDir, 0755, true);
    //     }

    //     $newImages = [];
    //     if ($request->hasFile('images')) {
    //         foreach ($request->file('images') as $image) {
    //             $filename = uniqid().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
    //             $image->move($userDir, $filename);
    //             $newImages[] = "/uploads/{$userId}/{$filename}";
    //         }
    //     }

    //     $newVideos = [];
    //     if ($request->hasFile('videos')) {
    //         foreach ($request->file('videos') as $video) {
    //             $filename = uniqid().'_'.Str::random(10).'.'.$video->getClientOriginalExtension();
    //             $video->move($userDir, $filename);
    //             $newVideos[] = "/uploads/{$userId}/{$filename}";
    //         }
    //     }

    //     // Process deleted files
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

    //     // Update gallery with new data
    //     $videoLinks = [];
    //     if ($request->filled('videoLinks')) {
    //         $videoLinks = collect(explode(',', $request->input('videoLinks')))
    //             ->map(fn($link) => trim($link))
    //             ->filter(fn($link) => !empty($link) && filter_var($link, FILTER_VALIDATE_URL))
    //             ->unique()
    //             ->values()
    //             ->toArray();
    //     }

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
    public function update(Request $request, Gallery $gallery)
    {
        $userId = $gallery->user_id;
        $galleryId = $gallery->id;
    
        // New folder path: uploads/{userId}/{galleryId}
        $galleryDir = base_path("../public_html/uploads/{$userId}/{$galleryId}");
    
        if (!File::exists($galleryDir)) {
            File::makeDirectory($galleryDir, 0755, true);
        }
    
        $newImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = uniqid().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();
                $image->move($galleryDir, $filename);
                $newImages[] = "/uploads/{$userId}/{$galleryId}/{$filename}";
            }
        }
    
        $newVideos = [];
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $video) {
                $filename = uniqid().'_'.Str::random(10).'.'.$video->getClientOriginalExtension();
                $video->move($galleryDir, $filename);
                $newVideos[] = "/uploads/{$userId}/{$galleryId}/{$filename}";
            }
        }
    
        // Handle deletions
        $deletedImages = json_decode($request->input('deleted_images', '[]'), true);
        $deletedVideos = json_decode($request->input('deleted_videos', '[]'), true);
    
        foreach ($deletedImages as $imageUrl) {
            $file = public_path($imageUrl);
            if (file_exists($file)) {
                unlink($file);
            }
        }
    
        foreach ($deletedVideos as $videoUrl) {
            $file = public_path($videoUrl);
            if (file_exists($file)) {
                unlink($file);
            }
        }
    
        // Handle video links
        $videoLinks = [];
        if ($request->filled('videoLinks')) {
            $videoLinks = collect(explode(',', $request->input('videoLinks')))
                ->map(fn($link) => trim($link))
                ->filter(fn($link) => !empty($link) && filter_var($link, FILTER_VALIDATE_URL))
                ->unique()
                ->values()
                ->toArray();
        }
    
        // Update gallery
        $gallery->update([
            'name' => $request->name,
            'category' => $request->category,
            'video_links' => $videoLinks,
            'images' => array_merge(
                array_diff($gallery->images ?? [], $deletedImages),
                $newImages
            ),
            'videos' => array_merge(
                array_diff($gallery->videos ?? [], $deletedVideos),
                $newVideos
            ),
        ]);
    
        return redirect()->back()->with([
            'success' => 'Gallery updated successfully!',
            'gallery' => [
                ...$gallery->fresh()->toArray(),
                'video_links' => $videoLinks
            ]
        ]);
    }

}