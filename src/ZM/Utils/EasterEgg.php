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
        if (!self::enabled()) {
            return null;
        }

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

    /**
     * 第二个彩蛋：如果你一直在追随最新的版本，那你一定是真心的爱好者吧
     * @see https://www.php.net/ChangeLog-8.php#8.2.0
     * @see https://pastebin.com/kzA9RAb7
     */
    public static function stepToEdge(): ?string
    {
        if (!self::enabled()) {
            return null;
        }

        $thank_you_php = <<<'EOF'
mbq2x3WlnaWBXxf1X6WdpXW4vbbDvLq5dcm9unXMts51zLp1ubrLusHExXXMurd1tsXFwb64tsm+xM
PIdbbDuXW+w7vByrrDuLq5dcK2w851ubrLusHExbrHyHW2w7l1xcfEv7q4y
ciDXxf1X6y6dcy2w8l1ycR1yb22w8B1p7bIwsrIdaG6x7nEx7uBda+6ust1qMrHtsjAvoF1yb26daWdpXW8x8TKxYF1pZ2ldbjEx7p1
ubrLusHExbrHyIF1uMTDyce+t8rJxMfIgXW2w7l1tsHBdcm9unXFusTFwbp1vsPLxMHLurl1vsN1wrbAvsO8dcm9unWlna
V1wbbDvMq2vLp1tsO5db7JyHW6uMTIzsjJusJ1tnW3usnJusd1xcG2uLp1u8THdbrLusfOxMO6
g18X9V+pvbbDwHXOxMp1yMR1wsq4vXW7xMd1tsHBdcm9tsl1zsTKdb22y7p1ucTDunW2w7l1ucSD
EOF;
        $version = PHP_VERSION_ID;
        $offset = ($version / 10 + 1 + ($version % 100) / 10);
        if (!is_int($offset)) {
            return null;
        }
        $i_want_to_say = '';
        $thank_you_php = base64_decode($thank_you_php);
        $checked = [false, false];
        for ($i = 0, $i_max = strlen($thank_you_php); $i < $i_max; ++$i) {
            $i_want_to_say .= chr(ord($thank_you_php[$i]) - $offset);
            if (!$checked[0]) {
                if (str_starts_with($i_want_to_say, 'D')) {
                    $checked[0] = true;
                } else {
                    return null;
                }
            } elseif (!$checked[1]) {
                if (str_ends_with($i_want_to_say, 'De')) {
                    $checked[1] = true;
                } else {
                    return null;
                }
            } elseif (file_exists('zm_data/ee-edge')) {
                return null;
            }
        }
        touch('zm_data/ee-edge');
        return $i_want_to_say . PHP_EOL;
    }

    private static function enabled(): bool
    {
        return config('global.i_dont_want_easter_egg', false) === false;
    }
}
