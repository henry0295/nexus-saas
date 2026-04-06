<?php
/**
 * Suprimir warnings específicos de Laravel que no afectan la funcionalidad
 * - tempnam(): file created in the system's temporary directory
 * 
 * NOTA: El patch se aplica en el Dockerfile con sed en Filesystem.php en lugar de usar
 * error handlers aquí, ya que los error handlers pueden interferir con el manejo de excepciones
 * de Laravel.
 */


