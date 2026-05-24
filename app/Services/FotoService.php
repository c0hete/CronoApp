<?php

namespace App\Services;

use App\Models\Configuracion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\JpegEncoder;

/**
 * Procesa la foto-evidencia de un marcaje (sección 8).
 *
 * - Es EVIDENCIA VISUAL DE PRESENCIA, no biometría ni reconocimiento facial.
 * - Se degrada (ancho/calidad config) para pesar ~30-50 KB.
 * - Se guarda FUERA de public, en disco 'fotos': storage/app/fotos/{empresa}/{año}/{mes}/.
 * - NUNCA se sirve por URL directa: solo vía controlador autorizado (dueño/admin).
 * - La foto es lo ÚNICO que se purga por retención; el registro del marcaje permanece.
 */
class FotoService
{
    private ImageManager $manager;

    public function __construct()
    {
        // GD ya viene compilado en la imagen (Dockerfile). Driver explícito = portable.
        $this->manager = new ImageManager(new GdDriver());
    }

    /**
     * Guarda una foto entrante (base64 o binario) degradada, y devuelve su ruta
     * relativa dentro del disco 'fotos' (lo que se persiste en marcajes.foto_evidencia).
     *
     * @return string ruta relativa (ej. "1/2026/05/uuid.jpg")
     */
    public function guardar(string $contenido, int $empresaId, string $uuid): string
    {
        $binario = $this->normalizar($contenido);

        $ancho     = (int) Configuracion::valor('foto_ancho_px', '640');
        $calidad   = (int) Configuracion::valor('foto_calidad', '70');
        $rotacion  = (int) Configuracion::valor('foto_rotacion', '0');

        $imagen = $this->manager->decode($binario);

        if ($rotacion !== 0) {
            $imagen->rotate(-$rotacion); // CSS/EXIF horario → Intervention antihorario
        }

        // Redimensionar a lo ancho manteniendo proporción, sin agrandar las chicas.
        $imagen->scaleDown(width: $ancho);

        $jpeg = $imagen->encode(new JpegEncoder(quality: $calidad));

        $ruta = sprintf('%d/%s/%s.jpg', $empresaId, date('Y'), date('m'));
        $rutaCompleta = $ruta . '/' . $this->nombreSeguro($uuid);

        Storage::disk('fotos')->put($rutaCompleta, (string) $jpeg);

        return $rutaCompleta;
    }

    /**
     * Acepta data-URI ("data:image/...;base64,...."), base64 pelado o binario crudo.
     */
    private function normalizar(string $contenido): string
    {
        if (Str::startsWith($contenido, 'data:')) {
            $contenido = (string) Str::after($contenido, ',');
        }

        // Si parece base64, decodificar; si no, asumir binario.
        $decodificado = base64_decode($contenido, true);

        return $decodificado !== false ? $decodificado : $contenido;
    }

    /**
     * Nombre de archivo no adivinable y seguro (sin path traversal).
     */
    private function nombreSeguro(string $uuid): string
    {
        $limpio = preg_replace('/[^a-zA-Z0-9\-]/', '', $uuid) ?: Str::uuid()->toString();

        return $limpio . '.jpg';
    }
}
