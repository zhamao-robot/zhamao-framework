#!/bin/bash

cq=$(screen -list | grep "cq")
if [[ "$cq" = "" ]]; then
    screen -dmS cq
fi
sleep 1s

screen -x -S cq -p 0 -X stuff "sudo docker run -it --rm --name cqbot -v "$(pwd)"/cqbot/:/root/ jesse2061/cqbot-swoole"
screen -x -S cq -p 0 -X stuff "\n"
screen -x -S cq -p 0 -X stuff "php start.php"
screen -x -S cq -p 0 -X stuff "\n"
