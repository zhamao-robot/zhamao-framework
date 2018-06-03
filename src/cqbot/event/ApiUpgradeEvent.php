<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午3:54
 */

class ApiUpgradeEvent extends Event
{
    public function __construct(swoole_http_client $cli) {
        Console::info("Upgraded API websocket");
        if(!$this->getFramework()->api->isConnected()) {
            echo "API connection lost.\nI will try next time after 30 second.\n";

            //这里本来该用异步计时器的，但是我太懒了，直接睡30秒先。
            //需要改用异步计时器的话，告诉我我会改的233333。
            sleep(30);
            $this->getFramework()->api = new \swoole_http_client($this->getFramework()->host, $this->getFramework()->api_port);
            $this->getFramework()->api->set(['websocket_mask' => true]);
            $this->getFramework()->api->on('message', [$this->getFramework(), "onApiMessage"]);
            $this->getFramework()->api->on("close", function ($cli){
                Console::info(Console::setColor("API connection closed", "red"));
            });
            $this->getFramework()->api->upgrade('/api/', [$this->getFramework(), "onUpgrade"]);
            return;
        }
        Buffer::$api = $this->getFramework()->api;
        Buffer::$event = $this->getFramework()->event;
        if ($data = file(CONFIG_DIR . "log/last_error.log")) {
            $last_time = file_get_contents(CONFIG_DIR . "log/error_flag");
            if (time() - $last_time < 2) {
                CQUtil::sendDebugMsg("检测到重复引起异常，停止服务器", 0);
                file_put_contents(CONFIG_DIR."log/last_error.log", "");
                $this->getFramework()->event->shutdown();
                return;
            }
            CQUtil::sendDebugMsg("检测到异常", 0);
            $msg = "";
            foreach ($data as $e) {
                $msg = $msg . $e . "\n";
            }
            CQUtil::sendDebugMsg($msg, 0);
            CQUtil::sendDebugMsg("[CQBot] 成功开启！", 0);
            file_put_contents(CONFIG_DIR . "error_flag", time());
            file_put_contents(CONFIG_DIR . "last_error.log", "");
        }
        else {
            CQUtil::sendDebugMsg("[CQBot] 成功开启！", 0);
        }
        CQUtil::sendAPI("_get_friend_list", ["get_friend_list"]);
        CQUtil::sendAPI("get_group_list", ["get_group_list"]);
        CQUtil::sendAPI("get_version_info", ["get_version_info"]);
    }
}