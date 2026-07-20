<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceEvent;
use App\Models\AttendancePhoto;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class AttendancePhotoService
{
    /**
     * Único punto que procesa y guarda una fotografía de evidencia: reduce tamaño,
     * genera miniatura y crea el registro AttendancePhoto ligado a la asistencia/evento.
     * Procesamiento síncrono a propósito (ver plan): evita que la galería muestre una
     * foto sin miniatura aún generada.
     */
    public function store(
        UploadedFile $file,
        Attendance $attendance,
        ?AttendanceEvent $event,
        Employee $employee,
        User $capturedBy,
        string $captureType,
        string $captureOrigin,
        Request $request,
        ?float $latitude = null,
        ?float $longitude = null,
        ?Carbon $deviceTime = null,
    ): AttendancePhoto {
        $manager = ImageManager::gd();
        $image = $manager->read($file->getRealPath());

        $now = now();
        $dir = "attendance-photos/{$now->format('Y')}/{$now->format('m')}/{$employee->id}";
        $uuid = (string) Str::uuid();

        $full = (clone $image)->scaleDown(width: 1280)->toJpeg(quality: 80);
        $thumb = (clone $image)->cover(300, 300)->toJpeg(quality: 70);

        $photoPath = "{$dir}/{$uuid}.jpg";
        $thumbPath = "{$dir}/{$uuid}_thumb.jpg";

        Storage::disk('local')->put($photoPath, (string) $full);
        Storage::disk('local')->put($thumbPath, (string) $thumb);

        return AttendancePhoto::create([
            'attendance_id' => $attendance->id,
            'attendance_event_id' => $event?->id,
            'employee_id' => $employee->id,
            'captured_by_user_id' => $capturedBy->id,
            'client_id' => $attendance->client_id,
            'service_point_id' => $attendance->service_point_id,
            'photo_path' => $photoPath,
            'thumbnail_path' => $thumbPath,
            'capture_type' => $captureType,
            'capture_origin' => $captureOrigin,
            'captured_at' => $now,
            'server_time' => $now,
            'device_time' => $deviceTime,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}
