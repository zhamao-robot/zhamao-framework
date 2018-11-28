<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/10/28
 * Time: 4:43 PM
 */

function printHelp() {
    echo color("{gold}=======CQBot-swoole=======");
    echo color("{gold}* 首次使用设置 *");
    echo color("{red}红色{r}为未设置，{green}绿色{r}为已设置，其他颜色为可选设置");
    echo color("[{green}1{r}] {" . (settings()["swoole_host"] == "" ? "red" : "green") . "}设置监听地址");
    echo color("[{green}2{r}] {" . (settings()["swoole_port"] == "" ? "red" : "green") . "}设置监听端口");
    echo color("[{green}3{r}] {" . (settings()["admin_group"] == "" ? "red" : "green") . "}设置管理群");
    echo color("[{green}4{r}] {yellow}设置管理员");
    echo color("[{green}5{r}] {yellow}设置连接token");
    echo color("[{green}6{r}] {lightlightblue}开始运行");
}
$id = "1";
while (true) {
    switch ($id) {
        case "1":
            echo color("请输入监听地址（默认0.0.0.0）：", "");
            $host = trim(fgets(STDIN));
            if ($host == "") {
                $properties["swoole_host"] = "0.0.0.0";
                echo color("{gray}已设置地址：0.0.0.0（默认）");
            } else {
                $properties["swoole_host"] = $host;
                echo color("{gray}已设置地址：" . $host);
            }
            $ls = settings();
            $ls["swoole_host"] = $properties["swoole_host"];
            file_put_contents("cqbot.json", json_encode($ls, 128 | 256));
            $id = 2;
            break;
        case "2":
            echo color("请输入监听端口（默认20000）：", "");
            $host = trim(fgets(STDIN));
            if ($host == "") {
                $properties["swoole_port"] = 20000;
                echo color("{gray}已设置端口：20000（默认）");
            } else {
                $properties["swoole_port"] = $host;
                echo color("{gray}已设置端口：" . $host);
            }
            $ls = settings();
            $ls["swoole_port"] = $properties["swoole_port"];
            file_put_contents("cqbot.json", json_encode($ls, 128 | 256));
            $id = 3;
            break;
        case "3":
            echo color("请输入机器人QQ的管理群（机器人必须已经在群内）：", "");
            $group = trim(fgets(STDIN));
            if ($group == "") {
                echo color("{red}请勿输入空数据！");
                break;
            }
            $properties["admin_group"] = $group;
            echo color("{gray}已设置机器人的管理群：" . $group);
            $ls = settings();
            $ls["admin_group"] = $properties["admin_group"];
            file_put_contents("cqbot.json", json_encode($ls, 128 | 256));
            $id = 4;
            break;
        case "4":
            echo color("请输入机器人管理员QQ（多个用空格分割）：", "");
            $group = trim(fgets(STDIN));
            if ($group == "") {
                echo color("{red}请勿输入空数据！");
                break;
            }
            $s = explodeMsg($group, true);
            $properties["admin"] = $s;
            echo color("{gray}已设置机器人的管理员：" . implode(", ",$s));
            $ls = settings();
            $ls["admin"] = $properties["admin"];
            file_put_contents("cqbot.json", json_encode($ls, 128 | 256));
            $id = 5;
            break;
        case "5":
            echo color("请输入连接框架的access_token（如果不用token则直接回车）：", "");
            $group = trim(fgets(STDIN));
            $properties["access_token"] = $group;
            if ($group == "") echo color("{gray}token为空，将不检测验证token！");
            else echo color("{gray}已设置websocket连接token：" . $group);
            $ls = settings();
            $ls["access_token"] = $properties["access_token"];
            file_put_contents("cqbot.json", json_encode($ls, 128 | 256));
            break 2;
        case "6":
            break 2;
        case "?":
        case "？":
        case "0":
            printHelp();
            break;
        default:
            echo color("{red}请输入正确的编号进行操作！\n在设置监听端口和监听地址后可开始运行服务器");
            break;
    }
}
echo color("{green}设置就绪！3秒后启动服务器。");
sleep(3);
