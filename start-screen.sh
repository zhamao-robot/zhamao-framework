#!/bin/bash

cq=$(screen -list | grep "cq")
if [[ "$cq" = "" ]]; then
    screen -dmS cq
fi
sleep 1s

#ls="cd "$(pwd)"/CQBot-swoole/"
#screen -x -S cq -p 0 -X stuff ${ls}
#screen -x -S cq -p 0 -X stuff "\n"
screen -x -S cq -p 0 -X stuff "php start.php"
screen -x -S cq -p 0 -X stuff "\n"