<?php
/**
 * Resource Optimizer
 * 
 * Provides methods for optimizing web resources such as CSS, JavaScript, and images.
 */
class ResourceOptimizer {

    /**
     * Minifies CSS content.
     * @param string $css The CSS content to minify.
     * @return string Minified CSS content.
     */
    public static function minifyCss($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remove whitespace and newlines
        $css = str_replace(array("\r\n", "\r", "\n", "\t"), '', $css);
        $css = preg_replace('/\s+/', ' ', $css); // Replace multiple spaces with single space
        // Remove spaces around selectors, braces, and colons
        $css = preg_replace('/ *(;|\{|\}) */', '$1', $css);
        $css = preg_replace('/ *: */', ':', $css);
        $css = preg_replace('/ *, */', ',', $css);
        $css = preg_replace('/;}/', '}', $css); // Remove semicolon before closing brace
        $css = trim($css);
        return $css;
    }

    /**
     * Minifies JavaScript content.
     * Note: For complex JS, a dedicated JS minifier (like UglifyJS via exec) is recommended.
     * This is a very basic minifier.
     * @param string $js The JavaScript content to minify.
     * @return string Minified JavaScript content.
     */
    public static function minifyJs($js) {
        // Remove comments
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js); // Multi-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js); // Single-line comments
        // Remove newlines and extra spaces
        $js = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $js);
        $js = preg_replace('/ +/', ' ', $js); // Replace multiple spaces with single space
        $js = trim($js);
        // Remove spaces around operators and semicolons
        $js = preg_replace('/ *([+\-*\/=<>!&|;,\(\)\{\}\[\]]) */', '$1', $js);
        return $js;
    }

    /**
     * Placeholder for image optimization.
     * In a real application, this would involve using image manipulation libraries
     * or external services to compress and resize images.
     * @param string $imagePath Path to the image file.
     * @return bool True on success, false on failure.
     */
    public static function optimizeImage($imagePath) {
        // Example: Using Imagick or GD library for optimization
        // $image = new Imagick($imagePath);
        // $image->setImageCompression(Imagick::COMPRESSION_JPEG);
        // $image->setImageCompressionQuality(75);
        // $image->stripImage(); // Remove all profiles and comments
        // return $image->writeImage($imagePath);
        
        // For now, just return true as a placeholder
        return true;
    }

    /**
     * Generates HTML for lazy loading images.
     * @param string $src The original image source.
     * @param string $alt The alt text for the image.
     * @param string $class Optional CSS class.
     * @return string HTML for lazy loaded image.
     */
    public static function getLazyLoadImageHtml($src, $alt = '', $class = '') {
        return '<img data-src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '" class="lazy-load ' . htmlspecialchars($class) . '">';
    }

    /**
     * Generates HTML for lazy loading iframes.
     * @param string $src The original iframe source.
     * @param string $class Optional CSS class.
     * @return string HTML for lazy loaded iframe.
     */
    public static function getLazyLoadIframeHtml($src, $class = '') {
        return '<iframe data-src="' . htmlspecialchars($src) . '" class="lazy-load ' . htmlspecialchars($class) . '" frameborder="0" allowfullscreen></iframe>';
    }
}
?>