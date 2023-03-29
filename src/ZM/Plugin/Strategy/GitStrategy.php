<?php

declare(strict_types=1);

namespace ZM\Plugin\Strategy;

use ZM\Utils\ZMRequest;
use ZM\Utils\ZMUtil;

class GitStrategy extends PluginInstallStrategy
{
    private string $git_api_link = 'https://api.github.com/repos/{owner}/{repo}/contents/composer.json';

    private string $token = '';

    /**
     * @throws \JsonException
     */
    public function install(array $option = []): bool
    {
        // 应用 git_api_link
        if (isset($option['git-api-link'])) {
            $this->git_api_link = $option['git-api-link'];
        }
        if (isset($option['github-token'])) {
            $this->token = $option['github-token'];
        }

        $git_url = parse_url($this->input);

        // GitHub 做特殊处理，直接调用 API 检查
        $is_github = false;
        $plugin_name = null;
        if ($git_url['host'] === 'github.com' && ($option['github-skip-check'] ?? false) !== true) {
            if (!$this->checkGitAPI($git_url, $plugin_name)) {
                return false;
            }
            $is_github = true;
        }

        // 使用 Composer 管理插件，将仓库链接绑定到 composer.json
        $this->logger->info('正在使用 Composer 下载并安装插件');
        $composer = ZMUtil::getComposerMetadata($this->root_composer_path);
        $origin_composer = $composer;
        $already_has_repo = false;

        // 不破坏原有队列，加入 GitHub 的 repo
        if (!isset($composer['repositories'])) {
            $composer['repositories'] = [];
        }
        if (is_assoc_array($composer['repositories'])) {
            $composer['repositories'] = [$composer['repositories']];
        }
        foreach ($composer['repositories'] as $v) {
            if (($v['url'] ?? '') === $this->input) {
                $already_has_repo = true;
                break;
            }
        }

        // 缓存一下对应的 repositories 属于的插件名称
        if (!$already_has_repo) {
            $composer['repositories'][] = [
                'type' => $is_github ? 'github' : 'git',
                'url' => $this->input,
                '.belongs' => $plugin_name,
            ];
        }

        // 写入 composer.json
        if (ZMUtil::putComposerMetadata($this->root_composer_path, $composer) === false) {
            $this->error = '写入 composer.json 失败';
            return false;
        }

        // 获取 Composer 命令行名称
        $env = ZMUtil::getComposerExecutable();

        // 这里返回 null 表明没调用成功 GitHub API 拿 composer.json 的元信息
        if ($plugin_name === null) {
            $this->error = '没有从 Git 获取到插件的元信息，目前无法从 GitHub 以外的 Git 仓库下载插件，后续会更新！';
            ZMUtil::putComposerMetadata($this->root_composer_path, $origin_composer);
            return false;
        }

        // 这里为空字符串表明插件名称不对，获取了空的，说明元 composer.json 文件出错
        if ($plugin_name === '') {
            $this->error = '获取插件名称失败！';
            ZMUtil::putComposerMetadata($this->root_composer_path, $origin_composer);
            return false;
        }

        // Git 方式拉取插件，在 Linux 系统上监听一下 Ctrl+C，这样即使用户想 Ctrl+C 断掉，也可以方便地恢复原来的 composer.json 文件内容。
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () use ($origin_composer) {
                echo "强行中断，恢复 Composer 中\n";
                ZMUtil::putComposerMetadata($this->root_composer_path, $origin_composer);
            });
        }

        // 引入
        passthru("{$env} require {$plugin_name}", $code);

        // 恢复 SIGINT 信号
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, SIG_IGN);
        }
        if ($code !== 0) {
            $this->error = '使用 composer 引入 Git 插件出现了一些错误，请看上方错误';
            ZMUtil::putComposerMetadata($this->root_composer_path, $origin_composer);
            return false;
        }
        return true;
    }

    /**
     * 用于调用 GitHub 的 API 用作不下载就检查插件是否合规
     *
     * @param array $git_url 解析后的链接
     */
    private function checkGitAPI(array $git_url, ?string &$plugin_name = null): bool
    {
        $this->logger->info('正在检查 GitHub 插件是否为框架插件');
        [, $owner, $repo] = explode('/', $git_url['path']);
        if (str_ends_with($repo, '.git')) {
            $repo = substr($repo, 0, -4);
        }

        // 调用 HTTP 客户端获取 API 信息
        $header = ['User-Agent' => 'zhamao-framework'];
        if ($this->token !== '') {
            $header['Authorization'] = 'token ' . $this->token;
        }
        $api = ZMRequest::get(
            str_replace(['{owner}', '{repo}'], [$owner, $repo], $this->git_api_link),
            $header,
            only_body: false
        );
        if ($api->getStatusCode() !== 200) {
            $this->error = "GitHub API 请求失败[{$api->getStatusCode()}]";
            if ($api->getStatusCode() === 403) {
                $this->error .= '可能是 API 滥用导致的，建议生成一个 GitHub Token。';
            }
            return false;
        }

        // 检查插件的 composer.json 是否合规
        $content = json_decode($api->getBody()->getContents(), true);
        if (isset($content['message'])) {
            $this->error = '该 GitHub 仓库中不存在 composer.json 文件！';
            return false;
        }
        $contents = implode('', array_map(fn ($x) => base64_decode($x), explode("\n", $content['content'])));
        $json = json_decode($contents, true);
        if (!$this->checkComposerIntegrity($json)) {
            return false;
        }
        $plugin_name = $json['name'];
        $this->installed_name = $plugin_name;
        return true;
    }
}
