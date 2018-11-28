<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/11/28
 * Time: 12:24 PM
 */

class StatusParser
{
    public static function parse($response, $origin) {
        switch ($response["retcode"]) {
            case 103:
            case 201:
            case -1:
            case -2:
            case -14:
            case 999:
            case 998:
                CQUtil::errorLog("API推送失败, retcode = " . $response["retcode"], "API ERROR", 0);
                break;
            case 200:
                break;
            default:
                CQUtil::errorLog("API推送失败, retcode = " . $response["retcode"] . "\n说明  = " . ErrorStatus::getMessage($response["retcode"]) . "\n" . json_encode($origin, 128 | 256), "API ERROR");

        }
    }
}