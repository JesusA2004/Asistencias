<?php

namespace App\Http\Controllers;

use App\Models\AttendancePhoto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendancePhotoController extends Controller
{
    public function show(AttendancePhoto $photo): StreamedResponse
    {
        $this->authorize('view', $photo);

        return Storage::disk('local')->response($photo->photo_path);
    }

    public function thumbnail(AttendancePhoto $photo): StreamedResponse
    {
        $this->authorize('view', $photo);

        return Storage::disk('local')->response($photo->thumbnail_path);
    }
}
