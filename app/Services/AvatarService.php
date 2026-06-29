<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarService
{
    private const SIZE = 320;

    public function storeUploaded(object $file, ?string $previous = null): string
    {
        $relative = 'avatars/avatar-'.Str::uuid().'.jpg';
        Storage::disk('public')->makeDirectory('avatars');
        $destination = Storage::disk('public')->path($relative);

        if (! $this->optimize($file->getRealPath(), $destination)) {
            $relative = $file->store('avatars', 'public');
        }

        $this->delete($previous, $relative);

        return $relative;
    }

    public function delete(?string $relative, ?string $except = null): void
    {
        if ($relative && $relative !== $except && str_starts_with($relative, 'avatars/')) {
            Storage::disk('public')->delete($relative);
        }
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
        $side = min($width, $height);
        $sourceX = (int) floor(($width - $side) / 2);
        $sourceY = (int) floor(($height - $side) / 2);
        $canvas = imagecreatetruecolor(self::SIZE, self::SIZE);
        $background = imagecolorallocate($canvas, 248, 250, 252);
        imagefill($canvas, 0, 0, $background);
        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            $sourceX,
            $sourceY,
            self::SIZE,
            self::SIZE,
            $side,
            $side,
        );

        $saved = @imagejpeg($canvas, $destinationPath, 85);
        imagedestroy($canvas);
        imagedestroy($source);

        return $saved;
    }
}
