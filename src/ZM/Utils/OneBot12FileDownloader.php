<?php

declare(strict_types=1);

namespace ZM\Utils;

use OneBot\V12\Object\ActionResponse;
use ZM\Context\BotContext;
use ZM\Exception\FileSystemException;
use ZM\Store\FileSystem;

/**
 * 一个支持 OneBot12 标准的文件传输器之下载部分
 */
class OneBot12FileDownloader
{
    /** @var string 下载路径 */
    private string $download_path;

    /** @var string 错误信息 */
    private string $err = '';

    public function __construct(private BotContext $ctx, private int $buffer_size = 524288)
    {
        $this->setDownloadPath(zm_dir(config('global.data_dir') . '/files'));
    }

    public function setDownloadPath(string $download_path): bool
    {
        $this->download_path = $download_path;
        try {
            FileSystem::createDir($this->download_path);
            return true;
        } catch (FileSystemException $e) {
            $this->err = $e->getMessage();
            return false;
        }
    }

    public function downloadFile(string $file_id, bool $fragmented = true): bool|string
    {
        if ($this->err !== '') {
            return false;
        }
        logger()->info('Downloading file ' . $file_id);
        if (!$fragmented) {
            $obj = $this->ctx->sendAction('get_file', [
                'file_id' => $file_id,
                'type' => 'data',
            ]);
            if (!$obj instanceof ActionResponse) {
                $this->err = 'coroutine not enabled, cannot receive action response';
                return false;
            }
            if ($obj->retcode !== 0) {
                $this->err = 'get file failed with code ' . $obj->retcode . ' : ' . $obj->message;
                return false;
            }
            $name = $obj->data['name'];
            $data = base64_decode($obj->data['data']);
        // TODO: Walle-Q 返回的 sha256 是空的
        /* if ($obj->data['sha256'] !== hash('sha256', $data)) {
            $this->err = 'sha256 mismatch between ' . $obj->data['sha256'] . ' and ' . hash('sha256', $data) . "\n" . json_encode($obj);
            return false;
        }*/
        } else {
            $obj = $this->ctx->sendAction('get_file_fragmented', [
                'stage' => 'prepare',
                'file_id' => $file_id,
            ]);
            if (!$obj instanceof ActionResponse) {
                $this->err = 'coroutine not enabled, cannot receive action response';
                return false;
            }
            if ($obj->retcode !== 0) {
                $this->err = 'get file fragment failed with code ' . $obj->retcode . ' : ' . $obj->message;
                return false;
            }
            $name = $obj->data['name'];
            $data = '';
            $total_size = $obj->data['total_size'];
            $sha256 = $obj->data['sha256'];
            $slice = intval($total_size / $this->buffer_size) + ($total_size % $this->buffer_size > 0 ? 1 : 0);
            for ($i = 0; $i < $slice; ++$i) {
                $trans = $this->ctx->sendAction('get_file_fragmented', [
                    'stage' => 'transfer',
                    'file_id' => $file_id,
                    'offset' => $i * $this->buffer_size,
                    'size' => $this->buffer_size,
                ]);
                if (!$trans instanceof ActionResponse) {
                    $this->err = 'coroutine not enabled, cannot receive action response';
                    return false;
                }
                if ($trans->retcode !== 0) {
                    $this->err = 'get file fragment failed with code ' . $trans->retcode . ' : ' . $trans->message;
                    return false;
                }
                $data .= base64_decode($trans->data['data']);
            }
            // TODO: Walle-Q 返回的 sha256 是空的
            /* if ($sha256 !== hash('sha256', $data)) {
                $this->err = 'sha256 mismatch';
                return false;
            }*/
        }
        if (str_contains($name, '..')) {
            $this->err = 'invalid file name';
            return false;
        }
        $path = zm_dir($this->download_path . '/' . $name);
        if (file_exists($path)) {
            $this->err = 'file already exists';
            return false;
        }
        if (!file_put_contents($path, $data)) {
            $this->err = 'failed to write file';
            return false;
        }
        return $path;
    }

    /**
     * 获取错误信息
     */
    public function getErr(): string
    {
        return $this->err;
    }
}
