<?php

declare(strict_types=1);

namespace ZM\Utils;

use OneBot\V12\Object\ActionResponse;
use ZM\Context\BotContext;

/**
 * 一个支持 OneBot12 标准的文件传输器之上传部分
 */
class OneBot12FileUploader
{
    private string $err = '';

    /**
     * 构建一个文件上传器
     *
     * @param BotContext $ctx         机器人上下文，用于调用发送动作
     * @param int        $buffer_size 分片传输的大小，默认为 65536 字节，建议调整小于 2MB
     */
    public function __construct(private BotContext $ctx, private int $buffer_size = 131072) {}

    /**
     * 通过文件内容上传一个文件
     * 当上传失败时返回 False，上传成功则返回 file_id
     *
     * @param  string      $filename 要上传的文件名
     * @param  string      $content  要上传的文件内容
     * @return bool|string 上传失败返回 false，可通过 getErr() 获取错误信息。上传成功返回 file_id
     * @throws \Throwable
     */
    public function uploadFromString(string $filename, string $content): bool|string
    {
        logger()->info('Uploading file, size: ' . strlen($content) . ', sha256: ' . hash('sha256', $content));
        $size = strlen($content);
        $offset = 0;
        // 文件本身小于分片大小，直接一个包发送
        if ($size <= $this->buffer_size) {
            $obj = $this->ctx->sendAction('upload_file', [
                'type' => 'data',
                'name' => $filename,
                'data' => base64_encode($content),
                'sha256' => hash('sha256', $content),
            ]);
            if (!$obj instanceof ActionResponse) {
                $this->err = 'prepare stage returns an non-response object';
                return false;
            }
            if ($obj->retcode !== 0) {
                $this->err = 'prepare stage returns an error: ' . $obj->retcode;
                return false;
            }
            return $obj->data['file_id'];
        }
        logger()->debug('分 ' . ceil($size / $this->buffer_size) . ' 个片');
        // 其他情况，使用分片的方式发送，依次调用 prepare, transfer, finish
        $obj = $this->ctx->sendAction('upload_file_fragmented', [
            'stage' => 'prepare',
            'name' => $filename,
            'total_size' => strlen($content),
        ]);
        if (!$obj instanceof ActionResponse) {
            $this->err = 'prepare stage returns an non-response object';
            return false;
        }
        if ($obj->retcode !== 0) {
            $this->err = 'prepare stage returns an error: ' . $obj->retcode;
            return false;
        }
        $file_id = $obj->data['file_id'];
        while ($offset < $size) {
            $data = substr($content, $offset, $this->buffer_size);
            $this->ctx->sendAction('upload_file_fragmented', [
                'stage' => 'transfer',
                'file_id' => $file_id,
                'offset' => $offset,
                'data' => base64_encode($data),
            ]);
            if (strlen($data) < $this->buffer_size) {
                break;
            }
            $offset += $this->buffer_size;
        }

        $final_obj = $this->ctx->sendAction('upload_file_fragmented', [
            'stage' => 'finish',
            'file_id' => $file_id,
            'sha256' => hash('sha256', $content),
        ]);
        if (!$final_obj instanceof ActionResponse) {
            $this->err = 'finish error with non-object';
            return false;
        }
        if ($final_obj->retcode !== 0) {
            $this->err = 'finish error: ' . $final_obj->retcode;
            return false;
        }
        return $final_obj->data['file_id'];
    }

    /**
     * 从本地路径上传一个文件
     *
     * @throws \Throwable
     */
    public function uploadFromPath(string $file_path): bool|string
    {
        if (file_exists($file_path)) {
            $name = pathinfo($file_path, PATHINFO_BASENAME);
            $content = file_get_contents($file_path);
            return $this->uploadFromString($name, $content);
        }
        $this->err = 'File from path ' . $file_path . ' does not exist.';
        return false;
    }

    /**
     * 获取错误消息
     */
    public function getErr(): string
    {
        return $this->err;
    }
}
