<?php

$dir = new RecursiveDirectoryIterator('c:/xampp/htdocs/HPA school Gestion/resources/views');
foreach (new RecursiveIteratorIterator($dir) as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $content = file_get_contents($file->getPathname());
        
        // Add whitespace-nowrap to tables to prevent horizontal compression
        $new = preg_replace('/<table class="([^"]*)"/', '<table class="$1 whitespace-nowrap"', $content);
        $new = str_replace('whitespace-nowrap whitespace-nowrap', 'whitespace-nowrap', $new);
        
        // Fix grid-cols-2 without responsive prefixes
        $new = str_replace('grid grid-cols-2', 'grid grid-cols-1 sm:grid-cols-2', $new);

        if ($new !== $content) {
            file_put_contents($file->getPathname(), $new);
            echo 'Updated ' . $file->getPathname() . PHP_EOL;
        }
    }
}
