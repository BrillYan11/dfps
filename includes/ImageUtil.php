<?php

class ImageUtil {
    /**
     * Compresses and saves an uploaded image.
     * 
     * @param string $source Path to the source image (tmp_name)
     * @param string $destination Path where the compressed image will be saved
     * @param int $quality Quality for JPEG (0-100), default 75
     * @param int $maxWidth Maximum width for the image, default 1200
     * @return bool True on success, false on failure
     */
    public static function compressImage($source, $destination, $quality = 75, $maxWidth = 1200) {
        $info = getimagesize($source);
        if ($info === false) return false;

        $mime = $info['mime'];
        $width = $info[0];
        $height = $info[1];

        // Calculate new dimensions if image is too wide
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = floor($height * ($maxWidth / $width));
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        $image = null;
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                // Handle transparency for PNG
                imagealphablending($image, false);
                imagesavealpha($image, true);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($source);
                break;
            default:
                return false;
        }

        if (!$image) return false;

        // Create new true color image for resizing
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Handle transparency for new image
        if ($mime == 'image/png' || $mime == 'image/gif' || $mime == 'image/webp') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save compressed image
        // We prefer saving as JPEG for maximum space saving, 
        // but for profile pics or specific products, we might want to keep PNG transparency.
        // For simplicity and space saving, we'll convert everything except PNG with alpha to JPEG?
        // Actually, let's keep the original format but apply compression.
        
        $result = false;
        switch ($mime) {
            case 'image/jpeg':
                $result = imagejpeg($newImage, $destination, $quality);
                break;
            case 'image/png':
                // PNG quality is 0-9 (0 = no compression, 9 = max compression)
                $pngQuality = floor((100 - $quality) / 10);
                $result = imagepng($newImage, $destination, $pngQuality);
                break;
            case 'image/gif':
                $result = imagegif($newImage, $destination);
                break;
            case 'image/webp':
                $result = imagewebp($newImage, $destination, $quality);
                break;
        }

        imagedestroy($image);
        imagedestroy($newImage);

        return $result;
    }
}
