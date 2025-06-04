<?php
/**
 * 将子包的 composer 相关依赖汇总到最外层
 */

use Symfony\Component\Process\Process;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/utils.php';

$require = collect();
$comments = collect();
//$replace = [];
$autoloadFiles = collect();
foreach (get_packages() as $package) {
    $json = json_decode(file_get_contents(path_join($package['dir_path'], 'composer.json')), true);
    $require = $require->merge($json['require'] ?? []);
    $comments = $comments->merge($json['_comment'] ?? []);
    //$replace[$json['name']] = 'self.version';
    if ($files = $json['autoload']['files'] ?? []) {
        $autoloadFiles = $autoloadFiles->merge(
            array_map(fn($fileName) => 'packages/' . $package['dir_name'] . '/' . $fileName, $files)
        );
    }
}

$composerFile = root_path('composer.json');
$json = json_decode(file_get_contents($composerFile), true);
$json['require'] = $require->toArray();
$json['_comment'] = $comments->unique()->values()->toArray();
//$json['replace'] = $replace;
$json['autoload']['files'] = $autoloadFiles->toArray();

$content = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

write_file($composerFile, $content, true);

echo "Normalize composer.json\n";
$process = Process::fromShellCommandline('composer normalize --no-check-lock --no-update-lock --indent-size=2 --indent-style=space', __DIR__ . '/../');
if ($code = $process->run()) {
    echo "Failed with code: {$code}\n";
    echo $process->getErrorOutput();
    exit($code);
}
echo "Done\n";
