<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    public function edit()
    {
        return Inertia::render('Profile/index', [
            'user' => Auth::user(),
        ]);
    }

    // public function update(Request $request)
    // {
    //     $request->validate([
    //         'first_name' => 'required|string|max:255',
    //         'last_name'  => 'required|string|max:255',
    //         'phone'      => 'required|string|max:15',
    //         'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    //         'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
    //         'brand_color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
    //     ]);

    //     $user = auth()->user();

    //     // Handle profile image upload
    //     if ($request->hasFile('profile_image')) {
    //         $image = $request->file('profile_image');
    //         $imageName = time() . '_profile.' . $image->getClientOriginalExtension();

    //         $image->move(public_path("/uploads/{$user->id}"), $imageName);

    //         // Delete old profile image if exists
    //         if ($user->profile_image) {
    //             $oldImagePath = public_path($user->profile_image);
    //             if (file_exists($oldImagePath)) {
    //                 unlink($oldImagePath);
    //             }
    //         }

    //         $user->profile_image = "/uploads/{$user->id}/{$imageName}";
    //     }

    //     // Handle logo upload
    //     if ($request->hasFile('logo')) {
    //         $logo = $request->file('logo');
    //         $logoName = time() . '_logo.' . $logo->getClientOriginalExtension();

    //         $logo->move(public_path("/uploads/{$user->id}"), $logoName);

    //         // Delete old logo if exists
    //         if ($user->logo) {
    //             $oldLogoPath = public_path($user->logo);
    //             if (file_exists($oldLogoPath)) {
    //                 unlink($oldLogoPath);
    //             }
    //         }

    //         $user->logo = "/uploads/{$user->id}/{$logoName}";
    //     }

    //     // Update brand color
    //     $user = auth()->user();
    //     $user->brand_color = $request->brand_color;

    //     $user->first_name = $request->first_name;
    //     $user->last_name = $request->last_name;
    //     $user->phone = $request->phone;
    //     $user->save();

    //     return redirect()->route('profile')->with('status', 'Profile updated successfully.');
    // }

    public function update(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'required|string|max:15',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'brand_color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $user = auth()->user();

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $imageName = time() . '_profile.' . $image->getClientOriginalExtension();
            
            // Create upload directory if it doesn't exist
            $uploadPath = $_SERVER['DOCUMENT_ROOT'] . "/uploads/{$user->id}";
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            try {
                // Move the new image
                $image->move($uploadPath, $imageName);
                
                // Delete old profile image if exists
                if ($user->profile_image) {
                    $oldImagePath = $_SERVER['DOCUMENT_ROOT'] . str_replace(asset(''), '', $user->profile_image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                
                // Save new image path
                $user->profile_image = "/uploads/{$user->id}/{$imageName}";
                
            } catch (Exception $e) {
                return redirect()->back()->with('error', 'Failed to upload profile image: ' . $e->getMessage());
            }
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logoName = time() . '_logo.' . $logo->getClientOriginalExtension();
            
            // Create upload directory if it doesn't exist
            $uploadPath = $_SERVER['DOCUMENT_ROOT'] . "/uploads/{$user->id}";
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            try {
                // Move the new logo (Fixed: use $logo instead of $image)
                $logo->move($uploadPath, $logoName);
                
                // Delete old logo if exists
                if ($user->logo) {
                    $oldLogoPath = $_SERVER['DOCUMENT_ROOT'] . $user->logo;
                    if (file_exists($oldLogoPath)) {
                        unlink($oldLogoPath);
                    }
                }
                
                // Save new logo path
                $user->logo = "/uploads/{$user->id}/{$logoName}";
                
            } catch (Exception $e) {
                return redirect()->back()->with('error', 'Failed to upload logo: ' . $e->getMessage());
            }
        }

        // Update user data
        $user->brand_color = $request->brand_color;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone = $request->phone;
        
        // Save all changes
        $user->save();

        return redirect()->route('profile')->with('status', 'Profile updated successfully.');
    }


    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required',
            'new_password'          => 'required|confirmed|min:8',
        ], [
            'new_password.confirmed' => 'The password confirmation does not match.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    // public function getUploadedFile($userId, $filename)
    // {
    //     $path = public_path("/uploads/{$userId}/{$filename}");

    //     if (!file_exists($path)) {
    //         abort(404);
    //     }

    //     return response()->file($path);
    // }

    public function getUploadedFile($userId, $filename)
    {
        // Validate user ID is numeric
        if (!is_numeric($userId)) {
            abort(404);
        }
        
        // Sanitize filename to prevent directory traversal attacks
        $filename = basename($filename);
        
        // Additional security: only allow specific file extensions
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg'];
        $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            abort(404);
        }
        
        // Construct the safe file path
        $path = public_path("/uploads/{$userId}/{$filename}");
        
        // Ensure the path is within the uploads directory (prevent directory traversal)
        $uploadsDir = realpath(public_path('/uploads'));
        $filePath = realpath($path);
        
        if (!$filePath || !str_starts_with($filePath, $uploadsDir)) {
            abort(404);
        }
        
        // Check if file exists
        if (!file_exists($path)) {
            abort(404);
        }
        
        // Set appropriate headers for the file type
        $mimeType = match($fileExtension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream'
        };
        
        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
            'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
        ]);
    }

    public function deleteImage()
    {
        $user = Auth::user();

        if ($user->profile_image) {
            $path = public_path($user->profile_image);

            if (file_exists($path)) {
                unlink($path);
            }

            $user->profile_image = null;
            $user->save();
        }

        return redirect()->back()->with('status', 'Profile image deleted.');
    }

    public function deleteLogo()
    {
        $user = Auth::user();

        if ($user->logo) {
            $path = public_path($user->logo);

            if (file_exists($path)) {
                unlink($path);
            }

            $user->logo = null;
            $user->save();
        }

        return redirect()->back()->with('status', 'Logo deleted.');
    }

    public function updateBrandColor(Request $request)
    {
        $request->validate([
            'brand_color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $user = Auth::user();
        $user->brand_color = $request->brand_color;
        $user->save();

        return response()->json([
            'message' => 'Brand color updated successfully',
            'brand_color' => $user->brand_color
        ]);
    }

}
