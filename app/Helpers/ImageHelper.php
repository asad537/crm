<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Generate responsive image srcset for different screen sizes
     * 
     * @param string $imagePath - The base image path
     * @param array $sizes - Array of widths to generate
     * @return string - srcset attribute value
     */
    public static function generateSrcset($imagePath, $sizes = [400, 600, 800, 1200])
    {
        $srcset = [];
        foreach ($sizes as $size) {
            $srcset[] = url("images/{$imagePath}") . " {$size}w";
        }
        return implode(', ', $srcset);
    }

    /**
     * Generate sizes attribute for responsive images
     * 
     * @param int $displayWidth - The displayed width in pixels
     * @return string - sizes attribute value
     */
    public static function generateSizes($displayWidth)
    {
        return "(max-width: 768px) {$displayWidth}px, (max-width: 1200px) " . ($displayWidth * 1.5) . "px, {$displayWidth}px";
    }

    /**
     * Get optimized image attributes for responsive loading
     * 
     * @param string $imagePath
     * @param string $alt
     * @param int $width
     * @param int $height
     * @param bool $eager - Whether to load eagerly (for LCP images)
     * @return array
     */
    public static function getOptimizedAttributes($imagePath, $alt, $width, $height, $eager = false)
    {
        return [
            'src' => url("images/{$imagePath}"),
            'alt' => $alt,
            'width' => $width,
            'height' => $height,
            'loading' => $eager ? 'eager' : 'lazy',
            'decoding' => 'async',
            'fetchpriority' => $eager ? 'high' : 'auto',
        ];
    }
}
