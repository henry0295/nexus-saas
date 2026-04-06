<?php
/**
 * Suprimir warnings específicos de Laravel que no afectan la funcionalidad
 * - tempnam(): file created in the system's temporary directory
 */

// Temporary directory for Laravel file operations
// This is necessary because in Docker, tempnam() may issue warnings
@ini_set('error_reporting', E_ALL & ~E_WARNING);

// Configurar error handling para suprimir warnings de tempnam
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Suprimir warning de tempnam en Filesystem.php
    if (strpos($errstr, 'tempnam()') !== false && strpos($errfile, 'Filesystem.php') !== false) {
        return true; // Suprimir el error
    }
    
    // Permitir que otros errores se procesen normalmente
    return false;  
}, E_WARNING | E_ALL);

