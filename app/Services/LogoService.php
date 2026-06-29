<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogoService
{
    private const MAX_WIDTH = 700;
    private const MAX_HEIGHT = 260;

    public function storeUploaded(object $file, ?string $previous = null): string
    {
        $relative = 'branding/logo-'.Str::uuid().'.jpg';
        $destination = Storage::disk('public')->path($relative);
        Storage::disk('public')->makeDirectory('branding');

        if (! $this->optimize($file->getRealPath(), $destination)) {
            $relative = $file->store('branding', 'public');
        }

        if ($previous && $previous !== $relative && str_starts_with($previous, 'branding/')) {
            Storage::disk('public')->delete($previous);
        }

        return $relative;
    }

    public function normalizeStored(?string $relative): ?string
    {
        if (! $relative || ! Storage::disk('public')->exists($relative)) {
            return null;
        }

        $source = Storage::disk('public')->path($relative);
        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg'], true) && filesize($source) <= 500_000) {
            return $relative;
        }

        $normalized = 'branding/logo-'.sha1($relative.'|'.filemtime($source)).'.jpg';
        $destination = Storage::disk('public')->path($normalized);
        if (! Storage::disk('public')->exists($normalized) && ! $this->optimize($source, $destination)) {
            return $relative;
        }

        return $normalized;
    }

    public function pdfPath(?string $relative): ?string
    {
        $normalized = $this->normalizeStored($relative);

        return $normalized && Storage::disk('public')->exists($normalized)
            ? Storage::disk('public')->path($normalized)
            : null;
    }

    private function optimize(string $sourcePath, string $destinationPath): bool
    {
        if (! extension_loaded('gd') || ! is_readable($sourcePath)) {
            return false;
        }

        $contents = @file_get_contents($sourcePath);
        $source = $contents !== false ? @imagecreatefromstring($contents) : false;
        if (! $source) {
            return false;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = min(1, self::MAX_WIDTH / max(1, $width), self::MAX_HEIGHT / max(1, $height));
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $saved = @imagejpeg($canvas, $destinationPath, 86);
        imagedestroy($canvas);
        imagedestroy($source);

        return $saved;
    }
}
