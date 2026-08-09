<?php

namespace App\Helpers;

/** Compone una imagen estatica del mapa (tiles de OpenStreetMap + marcador) para incrustar en el PDF del caso. */
class StaticMap
{
    public static function dataUri(float $lat, float $lng, int $zoom = 16, int $width = 640, int $height = 360): ?string
    {
        if (!extension_loaded('gd') || !function_exists('curl_init')) {
            return null;
        }

        try {
            $canvas = self::compose($lat, $lng, $zoom, $width, $height);
            if (!$canvas) return null;

            ob_start();
            imagepng($canvas);
            $png = ob_get_clean();
            imagedestroy($canvas);

            return 'data:image/png;base64,' . base64_encode($png);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function compose(float $lat, float $lng, int $zoom, int $width, int $height)
    {
        $n = 2 ** $zoom;
        $centerX = ($lng + 180) / 360 * $n;
        $latRad = deg2rad($lat);
        $centerY = (1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2 * $n;

        $originPxX = $centerX * 256 - $width / 2;
        $originPxY = $centerY * 256 - $height / 2;

        $tileXStart = (int) floor($originPxX / 256);
        $tileXEnd = (int) floor(($originPxX + $width - 1) / 256);
        $tileYStart = (int) floor($originPxY / 256);
        $tileYEnd = (int) floor(($originPxY + $height - 1) / 256);

        $canvas = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($canvas, 221, 221, 221);
        imagefill($canvas, 0, 0, $bg);

        $fetched = false;
        for ($tx = $tileXStart; $tx <= $tileXEnd; $tx++) {
            $wrappedX = (($tx % $n) + $n) % $n;
            for ($ty = $tileYStart; $ty <= $tileYEnd; $ty++) {
                if ($ty < 0 || $ty >= $n) continue;
                $tileData = self::fetchTile($wrappedX, $ty, $zoom);
                if (!$tileData) continue;
                $tileImg = @imagecreatefromstring($tileData);
                if (!$tileImg) continue;
                $destX = (int) round($tx * 256 - $originPxX);
                $destY = (int) round($ty * 256 - $originPxY);
                imagecopy($canvas, $tileImg, $destX, $destY, 0, 0, 256, 256);
                imagedestroy($tileImg);
                $fetched = true;
            }
        }
        if (!$fetched) {
            imagedestroy($canvas);
            return null;
        }

        self::drawMarker($canvas, (int) ($width / 2), (int) ($height / 2));
        self::drawAttribution($canvas, $width, $height);

        return $canvas;
    }

    private static function fetchTile(int $x, int $y, int $zoom): ?string
    {
        $subdomains = ['a', 'b', 'c'];
        $s = $subdomains[($x + $y) % 3];
        $url = "https://{$s}.tile.openstreetmap.org/{$zoom}/{$x}/{$y}.png";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_USERAGENT => 'FURD-SistemaGestionIncidentes/1.0 (+morenos706@gmail.com)',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($code === 200 && $data) ? $data : null;
    }

    /** Dibuja un pin estilo Leaflet (azul) apuntando exactamente al centro del canvas. */
    private static function drawMarker($canvas, int $cx, int $cy): void
    {
        $blue = imagecolorallocate($canvas, 42, 129, 203);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $bubbleCy = $cy - 14;

        imagefilledpolygon($canvas, [
            $cx - 8, $bubbleCy + 2,
            $cx + 8, $bubbleCy + 2,
            $cx, $cy,
        ], $blue);
        imagefilledellipse($canvas, $cx, $bubbleCy, 22, 22, $white);
        imagefilledellipse($canvas, $cx, $bubbleCy, 18, 18, $blue);
        imagefilledellipse($canvas, $cx, $bubbleCy, 7, 7, $white);
    }

    private static function drawAttribution($canvas, int $width, int $height): void
    {
        $text = '(c) OpenStreetMap contributors';
        $boxW = 6 * strlen($text) + 8;
        $white = imagecolorallocatealpha($canvas, 255, 255, 255, 40);
        $gray = imagecolorallocate($canvas, 85, 85, 85);
        imagefilledrectangle($canvas, $width - $boxW, $height - 14, $width, $height, $white);
        imagestring($canvas, 2, $width - $boxW + 4, $height - 13, $text, $gray);
    }
}
