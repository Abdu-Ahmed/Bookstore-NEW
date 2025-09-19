<?php

declare(strict_types=1);

/**
 * Load environment variables from .env file and provide env() helper function
 */

if (!function_exists('env')) {
    /**
     * Get environment variable value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        static $envVars = null;
        
        // Load .env file only once
        if ($envVars === null) {
            $envVars = [];
            $envPath = dirname(__DIR__, 2) . '/.env'; // Adjust path as needed
            
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                foreach ($lines as $line) {
                    // Skip comments
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    
                    // Parse key=value pairs
                    if (strpos($line, '=') !== false) {
                        [$envKey, $envValue] = explode('=', $line, 2);
                        $envKey = trim($envKey);
                        $envValue = trim($envValue);
                        
                        // Remove quotes if present
                        if (preg_match('/^([\'"])(.*)\1$/', $envValue, $matches)) {
                            $envValue = $matches[2];
                        }
                        
                        $envVars[$envKey] = $envValue;
                    }
                }
            }
        }
        
        // Check $_ENV first, then our loaded vars, then default
        return $_ENV[$key] ?? $envVars[$key] ?? $default;
    }
}