<?php

namespace App\Console\Commands;

use App\Models\AttendancePhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredAttendancePhotos extends Command
{
    protected $signature = 'attendance-photos:purge-expired';

    protected $description = 'Elimina fotografías de evidencia de asistencia que superaron el periodo de retención configurado.';

    public function handle(): int
    {
        $days = (int) setting('attendance_photo_retention_days', 90);
        $cutoff = now()->subDays($days);
        $deleted = 0;

        AttendancePhoto::where('captured_at', '<', $cutoff)
            ->chunkById(100, function ($photos) use (&$deleted) {
                foreach ($photos as $photo) {
                    Storage::disk('local')->delete([$photo->photo_path, $photo->thumbnail_path]);
                    $photo->delete();
                    $deleted++;
                }
            });

        $this->info("Se eliminaron {$deleted} fotografía(s) de evidencia con más de {$days} día(s) de antigüedad.");

        return self::SUCCESS;
    }
}
