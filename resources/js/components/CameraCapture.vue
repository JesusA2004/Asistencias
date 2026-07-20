<script setup lang="ts">
import { AlertCircle, Camera, RefreshCw, RotateCcw, Check, SwitchCamera } from '@lucide/vue';
import { onBeforeUnmount, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { notify } from '@/lib/notify';

const props = withDefaults(
    defineProps<{
        facingMode?: 'user' | 'environment';
        faceHint?: string;
    }>(),
    {
        facingMode: 'user',
        faceHint: 'Asegúrate de que el rostro sea claramente visible en la fotografía.',
    },
);

const emit = defineEmits<{
    captured: [file: File];
    cancel: [];
    error: [message: string];
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);

const stream = ref<MediaStream | null>(null);
const currentFacingMode = ref<'user' | 'environment'>(props.facingMode);
const canSwitchCamera = ref(false);
const status = ref<'starting' | 'live' | 'preview' | 'error'>('starting');
const errorMessage = ref('');
const previewUrl = ref<string | null>(null);
const capturedFile = ref<File | null>(null);

const stopStream = () => {
    stream.value?.getTracks().forEach((track) => track.stop());
    stream.value = null;
};

const mapError = (err: unknown): string => {
    const name = err instanceof DOMException ? err.name : '';

    if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
        return 'Debes permitir el acceso a la cámara para registrar asistencia.';
    }

    if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
        return 'Este dispositivo no tiene cámara disponible. No es posible registrar asistencia con evidencia obligatoria.';
    }

    if (name === 'NotReadableError') {
        return 'La cámara está siendo usada por otra aplicación. Ciérrala e intenta de nuevo.';
    }

    return 'No se pudo acceder a la cámara. Verifica los permisos o intenta con otro navegador.';
};

const detectMultipleCameras = async () => {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        canSwitchCamera.value = devices.filter((d) => d.kind === 'videoinput').length > 1;
    } catch {
        canSwitchCamera.value = false;
    }
};

const startCamera = async () => {
    status.value = 'starting';
    errorMessage.value = '';

    if (!navigator.mediaDevices?.getUserMedia) {
        errorMessage.value = 'Tu navegador no soporta captura de cámara. Usa un navegador actualizado.';
        status.value = 'error';
        emit('error', errorMessage.value);
        notify.error(errorMessage.value, 'No se pudo abrir la cámara');

        return;
    }

    try {
        stopStream();
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: currentFacingMode.value },
            audio: false,
        });

        if (videoRef.value) {
            videoRef.value.srcObject = stream.value;
            await videoRef.value.play();
        }

        status.value = 'live';
        void detectMultipleCameras();
    } catch (err) {
        errorMessage.value = mapError(err);
        status.value = 'error';
        emit('error', errorMessage.value);
        notify.error(errorMessage.value, 'No se pudo abrir la cámara');
    }
};

const switchCamera = () => {
    currentFacingMode.value = currentFacingMode.value === 'user' ? 'environment' : 'user';
    void startCamera();
};

const capture = () => {
    const video = videoRef.value;
    const canvas = canvasRef.value;

    if (!video || !canvas) {
        return;
    }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx?.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob(
        (blob) => {
            if (!blob) {
                return;
            }

            capturedFile.value = new File([blob], `captura-${Date.now()}.jpg`, { type: 'image/jpeg' });
            previewUrl.value = URL.createObjectURL(blob);
            status.value = 'preview';
            stopStream();
        },
        'image/jpeg',
        0.9,
    );
};

const retake = () => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = null;
    capturedFile.value = null;
    void startCamera();
};

const confirm = () => {
    if (!capturedFile.value) {
        return;
    }

    emit('captured', capturedFile.value);
};

const cancel = () => {
    stopStream();

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    emit('cancel');
};

onBeforeUnmount(() => {
    stopStream();

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

void startCamera();
</script>

<template>
    <div class="space-y-3">
        <div class="relative aspect-[4/3] w-full overflow-hidden rounded-lg bg-black">
            <video
                v-show="status === 'live' || status === 'starting'"
                ref="videoRef"
                class="h-full w-full object-cover"
                muted
                playsinline
            />

            <img v-if="status === 'preview' && previewUrl" :src="previewUrl" alt="Vista previa de la fotografía" class="h-full w-full object-cover" />

            <div v-if="status === 'starting'" class="absolute inset-0 flex items-center justify-center bg-black/60 text-white text-sm">
                Iniciando cámara...
            </div>

            <div v-if="status === 'error'" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/80 p-6 text-center text-white">
                <AlertCircle class="h-8 w-8 text-destructive" />
                <p class="text-sm">{{ errorMessage }}</p>
                <Button size="sm" variant="secondary" @click="startCamera">
                    <RefreshCw class="mr-2 h-3.5 w-3.5" /> Reintentar
                </Button>
            </div>

            <Button
                v-if="status === 'live' && canSwitchCamera"
                type="button"
                size="icon"
                variant="secondary"
                class="absolute right-2 top-2 h-8 w-8 rounded-full opacity-90"
                title="Cambiar cámara"
                @click="switchCamera"
            >
                <SwitchCamera class="h-4 w-4" />
            </Button>
        </div>

        <canvas ref="canvasRef" class="hidden" />

        <div class="space-y-1 text-center">
            <p v-if="status !== 'error'" class="text-xs font-medium text-foreground">{{ faceHint }}</p>
            <p class="text-xs text-muted-foreground">
                La fotografía se toma directamente con la cámara. No se permite subir imágenes desde la galería.
            </p>
        </div>

        <div class="flex justify-center gap-2">
            <template v-if="status === 'live'">
                <Button type="button" variant="outline" @click="cancel">Cancelar</Button>
                <Button type="button" @click="capture">
                    <Camera class="mr-2 h-4 w-4" /> Capturar foto
                </Button>
            </template>

            <template v-else-if="status === 'preview'">
                <Button type="button" variant="outline" @click="retake">
                    <RotateCcw class="mr-2 h-4 w-4" /> Repetir foto
                </Button>
                <Button type="button" @click="confirm">
                    <Check class="mr-2 h-4 w-4" /> Confirmar
                </Button>
            </template>

            <template v-else-if="status === 'error'">
                <Button type="button" variant="outline" @click="cancel">Cancelar</Button>
            </template>
        </div>
    </div>
</template>
