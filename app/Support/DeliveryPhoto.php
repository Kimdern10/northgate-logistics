<?php

namespace App\Support;

use App\Models\Shipment;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Proof of delivery photos. They are kept on the private disk (not in public/),
 * shrunk and saved as WebP with PHP's built-in GD, so they work on shared hosting.
 */
class DeliveryPhoto
{
    public const string DISK = 'local';

    public const int MAX_WIDTH = 1280;

    public const int QUALITY = 78;

    /**
     * Save the photo for a shipment, replacing any earlier one, and return its path.
     */
    public static function store(Shipment $shipment, UploadedFile $file): string
    {
        $path = 'proof-of-delivery/'.$shipment->tracking_number.'-'.Str::lower((string) Str::ulid()).'.webp';

        Storage::disk(self::DISK)->put($path, self::encode(self::load($file)));

        if ($shipment->delivery_photo_path) {
            Storage::disk(self::DISK)->delete($shipment->delivery_photo_path);
        }

        $shipment->forceFill(['delivery_photo_path' => $path])->save();

        return $path;
    }

    public static function response(Shipment $shipment): StreamedResponse
    {
        abort_unless($shipment->delivery_photo_path && Storage::disk(self::DISK)->exists($shipment->delivery_photo_path), 404);

        return Storage::disk(self::DISK)->response($shipment->delivery_photo_path, 'delivery-'.$shipment->tracking_number.'.webp', [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Read the upload into a GD image, turning phone photos the right way up.
     */
    private static function load(UploadedFile $file): GdImage
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $image instanceof GdImage) {
            throw new RuntimeException('The file could not be read as an image.');
        }

        if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            $exif = @exif_read_data($file->getRealPath());

            $rotated = match (is_array($exif) ? ($exif['Orientation'] ?? 1) : 1) {
                3 => imagerotate($image, 180, 0),
                6 => imagerotate($image, -90, 0),
                8 => imagerotate($image, 90, 0),
                default => $image,
            };

            if ($rotated instanceof GdImage) {
                $image = $rotated;
            }
        }

        return $image;
    }

    private static function encode(GdImage $source): string
    {
        $width = imagesx($source);
        $image = $source;

        if ($width > self::MAX_WIDTH) {
            $height = max(1, (int) round(imagesy($source) * self::MAX_WIDTH / $width));
            $image = imagecreatetruecolor(self::MAX_WIDTH, $height);
            imagecopyresampled($image, $source, 0, 0, 0, 0, self::MAX_WIDTH, $height, $width, imagesy($source));
        } elseif (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        ob_start();
        imagewebp($image, null, self::QUALITY);

        return (string) ob_get_clean();
    }
}
