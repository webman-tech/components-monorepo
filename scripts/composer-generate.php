<?php
/**
 * 功能：从 packages 目录下扫描出 composer 信息，合并到根下
 */

require __DIR__ . '/_common.php';

log_line("START " . __FILE__);

$require = [];
$comments = [];
$replace = [];
$autoloadPsr4 = [];
$autoloadFiles = [];
foreach (get_packages() as $package) {
    $json = json_decode(file_get_contents(path_join($package['dir_path'], 'composer.json')), true);
    $require = array_merge(
        $require,
        array_filter($json['require'] ?? [], fn(string $name): bool => !str_starts_with($name, 'webman-tech/'), ARRAY_FILTER_USE_KEY)
    );
    $comments = array_merge($comments, $json['_comment'] ?? []);
    $autoloadPsr4 = array_merge(
        $autoloadPsr4,
        array_map(fn(string $path): string => 'packages/' . $package['dir_name'] . '/' . $path, $json['autoload']['psr-4'] ?? []),
    );
    //$replace[$json['name']] = 'self.version';
    $autoloadFiles = array_merge(
        $autoloadFiles,
        array_map(fn($fileName): string => 'packages/' . $package['dir_name'] . '/' . $fileName, $json['autoload']['files'] ?? []),
    );
}

$composerFile = get_path('composer.json');
$json = json_decode(file_get_contents($composerFile), true);
$json['require'] = $require;
$json['_comment'] = array_values(array_unique($comments));
//$json['replace'] = $replace;
$json['autoload']['psr-4'] = $autoloadPsr4;
if (!isset($json['autoload']['files'])) {
    $json['autoload']['files'] = [];
}
if (!$autoloadFiles) {
    unset($json['autoload']['files']);
} else {
    $json['autoload']['files'] = $autoloadFiles;
}

$content = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

write_file($composerFile, $content, true);

log_line('格式化 composer.json');
run_shell('composer normalize --no-check-lock --no-update-lock --indent-size=2 --indent-style=space');

log_line('END');
