<?php
/**
 * 扫描 src 目录生成 gitsplit.yml
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/utils.php';

$splits = get_packages()
    ->map(fn(array $package) => <<<YML
  - prefix: "packages/{$package['dir_name']}"
    target: "https://\${GH_TOKEN}@github.com/{$package['git_name']}.git"
YML)
    ->implode("\n");

$content = <<<YML
# https://github.com/jderusse/docker-gitsplit
cache_url: "cache/gitsplit"
splits:
{$splits}
origins:
  - ^main$

YML;

write_file('.gitsplit.yml', $content);
