<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

function path_join(string $path, string $path2): string
{
    return rtrim(rtrim($path, '\\/') . DIRECTORY_SEPARATOR . ltrim($path2, '\\/'), '\\/');
}

function root_path(string $path = '')
{
    return path_join(realpath(__DIR__ . '/..'), $path);
}

function write_file(string $filename, string $content, bool $isAbsolutePath = false): void
{
    if (!$isAbsolutePath) {
        $filename = root_path($filename);
    }
    file_put_contents($filename, $content);

    write_log("File written: $filename");
}

function write_log(string $msg): void
{
    echo $msg . "\n";
}

function get_packages()
{
    static $packages;
    if ($packages) {
        return $packages;
    }

    $scanDir = root_path('packages');
    $files = new Filesystem();
    $packages = collect($files->directories($scanDir))
        ->filter(fn($dir) => !str_ends_with((string)$dir, '_template'))
        ->map(function ($dir) use ($scanDir) {
            $dirName = str_replace($scanDir . DIRECTORY_SEPARATOR, '', $dir);
            $composerName = 'webman-tech/' . Str::snake($dirName, '-');
            $gitName = $composerName;

            return [
                'dir_path' => $dir,
                'dir_name' => $dirName,
                'composer_name' => $composerName,
                'git_name' => $gitName,
                'class_namespace' => 'WebmanTech\\' . Str::studly($dirName),
            ];
        });

    return $packages;
}
