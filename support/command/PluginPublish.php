<?php
/**
 * PluginPublish.php
 * @author cdyun(121625706@qq.com)
 * @date 2026/3/12 18:40
 */

declare (strict_types=1);

namespace support\command;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\console\Command;
use think\console\input\Option;

/**
 * 插件安装.
 * @class PluginPublish
 */
class PluginPublish extends Command
{

    protected function configure()
    {
        $this->setName('plugin:publish')
            ->addOption('force', 'f', Option::VALUE_NONE, 'Overwrite any existing files')
            ->setDescription('Publish plugin and config assets for cdyun/thinkphp-framework');
    }

    public function handle()
    {
        $force = $this->input->getOption('force');
        $rootDir = $this->app->getRootPath();

        if (is_file($path = $rootDir . 'vendor/composer/installed.json')) {
            $packages = json_decode(@file_get_contents($path), true);
            // Compatibility with Composer 2.0
            if (isset($packages['packages'])) {
                $packages = $packages['packages'];
            }
            foreach ($packages as $package) {
                if (!empty($package['extra']['plugin'])) {

                    $installPath = $rootDir . 'vendor/' . $package['name'] . DIRECTORY_SEPARATOR;

                    foreach ((array)$package['extra']['plugin'] as $name => $file) {

                        $target = $rootDir . $file;
                        $source = $installPath . $name;

                        // 如果目标目录存在且不强制覆盖则跳过
                        if (is_dir($target) && !$force) {
                            $this->output->info("Dir {$target} exist!");
                            continue;
                        }

                        if (!is_dir($source)) {
                            $this->output->info("Dir {$source} not exist!");
                            continue;
                        }

                        $this->copyFolder($source, $target);
                    }
                }
            }

            $this->output->writeln('<info>Succeed!</info>');
        }
    }

    /**
     * 递归复制文件夹
     * @param string $src 源目录路径
     * @param string $dst 目标目录路径
     * @return void
     */
    private function copyFolder(string $src, string $dst): void
    {
        // 规范化源路径并检查
        $realSrc = realpath($src);
        if ($realSrc === false || !is_dir($realSrc)) {
            return;
        }

        // 创建目标目录（如果不存在）
        if (!is_dir($dst)) {
            if (!mkdir($dst, 0755, true)) {
                return;
            }
        }

        $realDst = realpath($dst);
        if ($realDst === false || !is_dir($realDst)) {
            return;
        }

        // 防止目录穿越攻击：确保源目录不是目标目录的子目录
        $realSrcNormalized = rtrim($realSrc, DIRECTORY_SEPARATOR);
        $realDstNormalized = rtrim($realDst, DIRECTORY_SEPARATOR);

        if ($realSrcNormalized === $realDstNormalized ||
            str_starts_with($realSrcNormalized, $realDstNormalized . DIRECTORY_SEPARATOR)) {
            return;
        }

        try {
            $iterator = new RecursiveDirectoryIterator($realSrcNormalized, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS);
            $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file) {
                // 跳过符号链接，防止无限循环和安全问题
                if ($file->isLink()) {
                    continue;
                }

                $srcPath = $file->getPathname();
                $relativePath = substr($srcPath, strlen($realSrcNormalized) + 1);

                if ($relativePath === false || $relativePath === '') {
                    continue;
                }

                $dstPath = $realDstNormalized . DIRECTORY_SEPARATOR . $relativePath;

                if ($file->isDir()) {
                    if (!is_dir($dstPath)) {
                        if (!mkdir($dstPath, 0755, true)) {
                            return;
                        }
                    }
                } else {
                    if (!copy($srcPath, $dstPath)) {
                        return;
                    }
                }
            }

            return;
        } catch (\Exception $e) {
            return;
        }
    }

}