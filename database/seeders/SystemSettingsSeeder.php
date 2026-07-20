<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $warningText = 'Este sistema utiliza evidencia fotográfica para agilizar y validar el proceso de asistencia. '
            .'Al continuar, aceptas que la información capturada será revisada por personal autorizado. '
            .'Registrar información falsa, tomar fotografías que no correspondan al colaborador, alterar la evidencia '
            .'o capturar asistencia de forma indebida puede derivar en sanciones conforme a las políticas internas de la empresa.';

        $settings = [
            [
                'key' => 'allow_employee_self_attendance',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'asistencia_colaborador',
                'description' => 'Permite que el colaborador registre su propia asistencia (entrada/salida).',
            ],
            [
                'key' => 'employee_self_attendance_requires_photo',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'asistencia_colaborador',
                'description' => 'Exige fotografía obligatoria al colaborador para registrar su asistencia.',
            ],
            [
                'key' => 'employee_self_attendance_allow_exit',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'asistencia_colaborador',
                'description' => 'Permite que el colaborador también registre su salida (no solo entrada).',
            ],
            [
                'key' => 'employee_self_attendance_requires_location',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'asistencia_colaborador',
                'description' => 'Solicita la ubicación del dispositivo al registrar asistencia propia.',
            ],
            [
                'key' => 'supervisor_capture_requires_photo',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'captura_supervisor',
                'description' => 'Exige que el supervisor tome fotografía al capturar asistencia de un colaborador.',
            ],
            [
                'key' => 'supervisor_capture_photo_per_employee',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'captura_supervisor',
                'description' => 'Exige una fotografía por cada colaborador capturado, no una foto general.',
            ],
            [
                'key' => 'attendance_photo_review_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'evidencia',
                'description' => 'Habilita el módulo de revisión de evidencias fotográficas.',
            ],
            [
                'key' => 'attendance_photo_retention_days',
                'value' => '90',
                'type' => 'integer',
                'group' => 'evidencia',
                'description' => 'Días de retención de las fotografías de evidencia antes de eliminarse automáticamente.',
            ],
            [
                'key' => 'attendance_warning_text',
                'value' => $warningText,
                'type' => 'text',
                'group' => 'evidencia',
                'description' => 'Texto de advertencia mostrado antes de capturar asistencia con evidencia fotográfica.',
            ],
            [
                'key' => 'attendance_warning_version',
                'value' => '1',
                'type' => 'integer',
                'group' => 'evidencia',
                'description' => 'Versión del aviso de evidencia fotográfica. Incrementar fuerza a todos los usuarios a aceptarlo de nuevo.',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
