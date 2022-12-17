<?php

declare(strict_types=1);

namespace ZM\Utils;

class EasterEgg
{
    /**
     * 第一个彩蛋：把炸毛的源码修改为 777 权限是不安全的，会蹦出牛告诉你哦
     */
    public static function checkFrameworkPermissionCall(): ?string
    {
        // caidan
        $str = substr(sprintf('%o', fileperms(__FILE__)), -4);
        if ($str == '0777') {
            $table = ['@' => '9fX1', '!' => 'ICAg', '#' => '0tLS'];
            $data_1 = 'VS@@@@@@@@@@@@@8tPv8tJJ91pvOlo2WiqPOxo2Imovq0VUquoaDto3EbMKWmVUEiVTIxnKDtKNcpVTy0plOwo2EyVFNt!!!!!!!!!VP8XVP#############0tPvNt';
            $data_2 = $data_1 . '!!KPNtVS5sK14X!!!KPNtXT9iXIksK1@9sPvNt!!!VPusKlyp!!VPypY1jX!!!!!VUk8YF0gYKptsNbt!!!!!sUjt!VUk8Pt==';
            $str = base64_decode(str_replace(array_keys($table), array_values($table), str_rot13($data_2)));
            return $str . PHP_EOL;
        }
        return null;
    }
}
