<?php

$dir = new RecursiveDirectoryIterator('c:/xampp/htdocs/HPA school Gestion/resources/views');
foreach (new RecursiveIteratorIterator($dir) as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $content = file_get_contents($file->getPathname());
        $new = $content;
        
        // 1. Remove overflow-x-auto from the p-6 container
        $new = str_replace('p-6 text-gray-900 overflow-x-auto', 'p-6 text-gray-900', $new);
        
        // 2. Wrap the table in <div class="overflow-x-auto"> if it's not already wrapped
        // We will look for <table and replace it with <div class="overflow-x-auto"><table
        // But we need to make sure we don't wrap it twice.
        // Also we need to close the div after </table>
        
        if (strpos($new, '<div class="overflow-x-auto">') === false && strpos($new, '<table') !== false) {
            $new = preg_replace('/(<table[^>]*>)/', '<div class="overflow-x-auto">' . "\n" . '                        $1', $new);
            $new = preg_replace('/(<\/table>)/', '$1' . "\n" . '                    </div>', $new);
        }

        if ($new !== $content) {
            file_put_contents($file->getPathname(), $new);
            echo 'Updated ' . $file->getPathname() . PHP_EOL;
        }
    }
}
