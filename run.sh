#!/bin/bash
docker run --rm \
--add-host localhost:$(ifconfig eth0 |grep "inet addr"| cut -f 2 -d ":"|cut -f 1 -d " ") -ti \
--name cqhttp \
-v $(pwd)/coolq:/home/user/coolq \
-p 9000:9000 \
-p 10000:10000 \
-e CQHTTP_USE_WS=yes \
-e CQHTTP_WS_HOST=0.0.0.0 \
-e CQHTTP_WS_PORT=10000 \
-e CQHTTP_USE_WS_REVERSE=yes \
-e CQHTTP_WS_REVERSE_EVENT_URL=ws://localhost:20000/ \
richardchien/cqhttp:latest
