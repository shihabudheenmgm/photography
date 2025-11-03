<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class ProfileImageController extends Controller
{
    public function profileImageShow()
    {
        $user = Auth::user();

        if (!$user->profile_image) {
            abort(404);
        }

        $path = public_path($user->profile_image);

        if (!File::exists($path)) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        return response($file)->header('Content-Type', $type);
    }
}



