<?php
/**
 * Simple File-Based Cache Helper
 */
class CacheHelper {
    private $cacheDir;
    private $defaultExpiration = 3600; // 1 Hour

    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Secure cache directory
        if (!file_exists($this->cacheDir . '.htaccess')) {
            file_put_contents($this->cacheDir . '.htaccess', "deny from all");
        }
    }

    /**
     * Get item from cache
     * @param string $key Unique cache key
     * @return mixedData or false if processing failed or expired
     */
    public function get($key) {
        $filename = $this->getFilename($key);
        if (!file_exists($filename)) {
            return false;
        }

        $content = file_get_contents($filename);
        $data = json_decode($content, true);

        if (!$data) {
            return false;
        }

        // Check expiration
        if (time() > $data['expires']) {
            unlink($filename);
            return false;
        }

        return $data['payload'];
    }

    /**
     * Set item to cache
     * @param string $key Unique cache key
     * @param mixed $data Data to cache
     * @param int $seconds Expiration in seconds
     */
    public function set($key, $data, $seconds = null) {
        if ($seconds === null) {
            $seconds = $this->defaultExpiration;
        }

        $payload = [
            'expires' => time() + $seconds,
            'payload' => $data
        ];

        $filename = $this->getFilename($key);
        return file_put_contents($filename, json_encode($payload));
    }

    /**
     * Delete item from cache
     */
    public function delete($key) {
        $filename = $this->getFilename($key);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return false;
    }

    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function getFilename($key) {
        return $this->cacheDir . md5($key) . '.json';
    }
}
?>
