<?php
/**
 * 将子包的 composer 相关依赖汇总到最外层
 */

use Symfony\Component\Process\Process;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/utils.php';

$require = collect();
$comments = collect();
$replace = [];
$autoloadFiles = collect();
$skillsSources = [];
$autoloadPsr4 = collect([
    'WebmanTech\ComponentsMonorepo\\' => 'src',
]);
foreach (get_packages() as $package) {
    $json = json_decode(file_get_contents(path_join($package['dir_path'], 'composer.json')), true);
    $require = $require->merge($json['require'] ?? []);
    $comments = $comments->merge($json['_comment'] ?? []);
    $autoloadPsr4 = $autoloadPsr4->merge([
        $package['class_namespace'] . '\\' => "packages/{$package['dir_name']}/src",
    ]);
    $replace[$json['name']] = 'self.version';
    if ($files = $json['autoload']['files'] ?? []) {
        $autoloadFiles = $autoloadFiles->merge(
            array_map(fn($fileName) => 'packages/' . $package['dir_name'] . '/' . $fileName, $files)
        );
    }
    // 聚合技能发现源：子包存在 skills/ 目录即作为 llm/skills 的 donor 源
    if (is_dir(path_join($package['dir_path'], 'skills'))) {
        $skillsSources[] = "packages/{$package['dir_name']}/skills";
    }
}

$composerFile = root_path('composer.json');
$json = json_decode(file_get_contents($composerFile), true);
$json['require'] = $require->toArray();
$json['_comment'] = $comments->unique()->values()->toArray();
$json['replace'] = $replace;
$json['autoload']['psr-4'] = $autoloadPsr4->toArray();
$json['autoload']['files'] = $autoloadFiles->toArray();

// 维护 extra.skills.source：仅当存在 skills 源的包时才写入，避免空数组被 llm/skills 拒绝
if ($skillsSources !== []) {
    $json['extra']['skills']['source'] = $skillsSources;
} else {
    unset($json['extra']['skills']['source']);
    if (empty($json['extra']['skills'])) {
        unset($json['extra']['skills']);
    }
    if (empty($json['extra'])) {
        unset($json['extra']);
    }
}

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
