<?php


namespace ZM\API;


use Swoole\Coroutine\Http\Client;
use ZM\Console\Console;

class TuringAPI
{
    /**
     * 请求图灵API，返回图灵的消息
     * @param $msg
     * @param $user_id
     * @param $api
     * @return string
     */
    public static function getTuringMsg($msg, $user_id, $api) {
        $origin = $msg;
        if (($cq = CQ::getCQ($msg)) !== null) {//如有CQ码则去除
            if ($cq["type"] == "image") {
                $url = $cq["params"]["url"];
                $msg = str_replace(mb_substr($msg, $cq["start"], $cq["end"] - $cq["start"] + 1), "", $msg);
            }
            $msg = trim($msg);
        }
        //构建将要发送的json包给图灵
        $content = [
            "reqType" => 0,
            "userInfo" => [
                "apiKey" => $api,
                "userId" => $user_id
            ]
        ];
        if ($msg != "") {
            $content["perception"]["inputText"]["text"] = $msg;
        }
        $msg = trim($msg);
        if (mb_strlen($msg) < 1 && !isset($url)) return "请说出你想说的话";
        if (isset($url)) {
            $content["perception"]["inputImage"]["url"] = $url;
            $content["reqType"] = 1;
        }
        if (!isset($content["perception"])) return "请说出你想说的话";
        $client = new Client("openapi.tuling123.com", 80);
        $client->setHeaders(["Content-type" => "application/json"]);
        $client->post("/openapi/api/v2", json_encode($content, JSON_UNESCAPED_UNICODE));
        $api_return = json_decode($client->body, true);
        if (!isset($api_return["intent"]["code"])) return "XD 哎呀，我脑子突然短路了，请稍后再问我吧！";
        $status = self::getResultStatus($api_return);
        if ($status !== true) {
            if ($status == "err:输入文本内容超长(上限150)") return "你的话太多了！！！";
            if ($api_return["intent"]["code"] == 4003) {
                return "哎呀，我刚才有点走神了，可能忘记你说什么了，可以重说一遍吗";
            }
            Console::error(zm_internal_errcode("E00038") . "图灵机器人发送错误！\n错误原始内容：" . $origin . "\n来自：" . $user_id . "\n错误信息：" . $status);
            //echo json_encode($r, 128|256);
            return "哎呀，我刚才有点走神了，要不一会儿换一种问题试试？";
        }
        $result = $api_return["results"];
        //Console::info(Console::setColor(json_encode($result, 128 | 256), "green"));
        $final = "";
        foreach ($result as $v) {
            switch ($v["resultType"]) {
                case "url":
                    $final .= "\n" . $v["values"]["url"];
                    break;
                case "text":
                    $final .= "\n" . $v["values"]["text"];
                    break;
                case "image":
                    $final .= "\n" . CQ::image($v["values"]["image"]);
                    break;
            }
        }
        return trim($final);
    }

    public static function getResultStatus($r) {
        switch ($r["intent"]["code"]) {
            case 5000:
                return "err:无解析结果";
            case 4000:
            case 6000:
                return "err:暂不支持该功能";
            case 4001:
                return "err:加密方式错误";
            case 4005:
            case 4002:
                return "err:无功能权限";
            case 4003:
                return "err:该apikey没有可用请求次数";
            case 4007:
                return "err:apikey不合法";
            case 4100:
                return "err:userid获取失败";
            case 4200:
                return "err:上传格式错误";
            case 4300:
                return "err:批量操作超过限制";
            case 4400:
                return "err:没有上传合法userid";
            case 4500:
                return "err:userid申请个数超过限制";
            case 4600:
                return "err:输入内容为空";
            case 4602:
                return "err:输入文本内容超长(上限150)";
            case 7002:
                return "err:上传信息失败";
            case 8008:
                return "err:服务器错误";
            default:
                return true;
        }
    }
}