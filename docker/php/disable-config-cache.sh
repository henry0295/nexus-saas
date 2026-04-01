#!/bin/sh
# Deshabilitar config en cache para development debugging
# Esto fuerza a Laravel a cargar la configuración sin cachear
# permitiendo ver errores reales en lugar de "Server Error" genérico

cd /app

# IMPORTANTE: Eliminar el cache de bootstrap
rm -rf bootstrap/cache/config.php
rm -rf bootstrap/cache/*.php

# No ejecutar config:cache en este script
# Dejar que Laravel cargue la config en tiempo real

# Ejecutar el servidor PHP
exec php-fpm
