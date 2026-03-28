<?php
// includes/cache_helper.php

class CacheHelper {
    private $cacheDir;
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }
    
    public function get($key, $duration = 300) {
        $filename = $this->cacheDir . md5($key) . '.cache';
        
        if (file_exists($filename)) {
            // Check if expired
            if ((time() - filemtime($filename)) < $duration) {
                return unserialize(file_get_contents($filename));
            }
        }
        
        return false;
    }
    
    public function set($key, $data) {
        $filename = $this->cacheDir . md5($key) . '.cache';
        return file_put_contents($filename, serialize($data));
    }
    
    public function delete($key) {
        $filename = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
}
?>
