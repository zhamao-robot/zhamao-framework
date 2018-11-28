<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/3/29
 * Time: 11:32
 */

class Console
{
    static function setColor($string, $color = "") {
        switch ($color) {
            case "red":
                return "\x1b[38;5;203m" . $string . "\x1b[m";
            case "green":
                return "\x1b[38;5;83m" . $string . "\x1b[m";
            case "yellow":
                return "\x1b[38;5;227m" . $string . "\x1b[m";
            case "blue":
                return "\033[34m" . $string . "\033[0m";
            case "lightpurple":
                return "\x1b[38;5;207m" . $string . "\x1b[m";
            case "lightblue":
                return "\x1b[38;5;87m" . $string . "\x1b[m";
            case "gold":
                return "\x1b[38;5;214m" . $string . "\x1b[m";
            case "gray":
                return "\x1b[38;5;59m" . $string . "\x1b[m";
            case "pink":
                return "\x1b[38;5;207m" . $string . "\x1b[m";
            case "lightlightblue":
                return "\x1b[38;5;63m" . $string . "\x1b[m";
            default:
                return $string;
        }
    }


    static function debug($obj, $head = null) {
        if ($head === null) $head = "[" . date("H:i:s") . " DEBUG]";
        if ((Cache::get("info_level") ?? 0) < 2) return;
        if (!is_string($obj)) var_dump($obj);
        else echo(self::setColor($head . $obj, "green") . "\n");
    }

    static function error($obj, $head = null) {
        if ($head === null) $head = "[" . date("H:i:s") . " ERROR]";
        if (!is_string($obj)) var_dump($obj);
        else echo(self::setColor($head . $obj, "red") . "\n");
    }

    static function info($obj, $head = null, $tail = "\n") {
        if ($head === null) $head = "[" . date("H:i:s") . " INFO] ";
        if (!is_string($obj)) var_dump($obj);
        else echo(self::setColor($head . $obj, "blue") . $tail);
    }

    static function put($obj, $color = "") {
        if (!is_string($obj)) var_dump($obj);
        else echo(self::setColor($obj, $color) . "\n");
    }

    public static function msg($obj) {
        if (Cache::get("info_level") >= 1) {
            switch ($obj["action"]) {
                case "send_private_msg":
                    Console::put(Console::setColor("[".date("H:i:s")." PRIVATE] ", "blue").Console::setColor($obj["params"]["user_id"] ?? "", "yellow") . Console::setColor(" > ", "gray").($obj["params"]["message"] ?? ""));
                    break;
                case "send_group_msg":
                    Console::put(Console::setColor("[".date("H:i:s")." GROUP:".$obj["params"]["group_id"]."] ", "blue").Console::setColor($obj["params"]["user_id"] ?? "", "yellow") . Console::setColor(" > ", "gray").($obj["params"]["message"] ?? ""));
                    break;
                case "send_discuss_msg":
                    Console::put(Console::setColor("[".date("H:i:s")." DISCUSS:".$obj["params"]["discuss_id"]."] ", "blue").Console::setColor($obj["params"]["user_id"] ?? "", "yellow") . Console::setColor(" > ", "gray").($obj["params"]["message"] ?? ""));
                    break;
                case "send_msg":
                    $obj["action"] = "send_".$obj["message_type"]."_msg";
                    self::msg($obj);
                    break;
                default:
                    break;
            }
        }
    }
}