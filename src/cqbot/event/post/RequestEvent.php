<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/10/15
 * Time: 10:36 AM
 */

class RequestEvent extends Event
{
    public function __construct($req) {
        switch ($req["request_type"]) {
            case "friend":
                //好友添加请求处理
                $comment = $req["comment"];//验证信息
                $flag = $req["flag"];//添加好友用的flag，用于处理好友
                $user_id = $req["user_id"];

                //如果不需要将好友添加信息发到群里，请注释下面这行
                CQUtil::sendDebugMsg("有用户请求添加好友啦！\n用户QQ：" . $user_id . "\n验证信息：" . $comment . "\n添加flag：" . $flag, $req["self_id"], 0);

                //如果不需要要自动同意，请注释下面这几行
                $params = ["flag" => $flag, "approve" => true];
                CQUtil::sendAPI(CQUtil::getApiConnectionByQQ($req["self_id"])->fd, ["action" => "set_friend_add_request", "params" => $params], ["set_friend_add_request", $req["user_id"]]);
                CQUtil::sendGroupMsg(Buffer::get("admin_group"), "已自动同意 " . $req["user_id"], $req["self_id"]);
                break;
            case "group":
                switch ($req["sub_type"]) {
                    case "invite":
                        $comment = $req["comment"];//验证信息
                        $user_id = $req["user_id"];//用户QQ
                        $group_id = $req["group_id"];//被邀请进群的群号
                        $flag = $req["flag"];//同意加群用的flag，用于处理是否加群
                        //TODO: 邀请登录号入群事件处理
                        break;
                    case "add":
                        $comment = $req["comment"];//验证信息
                        $user_id = $req["user_id"];//用户QQ
                        $group_id = $req["group_id"];//用户申请加群的群号
                        $flag = $req["flag"];//同意加群用的flag，用于处理是否同意其入群
                        //TODO: 机器人是管理员，处理入群请求事件
                        break;

                }
        }
    }
}