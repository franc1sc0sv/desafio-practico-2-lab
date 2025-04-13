#!/bin/sh

echo "📂 Directorio actual: $(pwd)"
echo "👀 Observando cambios en archivos PHP..."

# Asegúrate de que estos directorios existen en tu proyecto
find ./src ./public ./app -type f -name '*.php' | entr -r php public/index.php

echo "🛑 El watcher terminó (esto no debería pasar si el loop corre bien)"
