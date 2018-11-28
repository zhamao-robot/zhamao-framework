#!/bin/bash

sudo docker run -it --rm --name cqbot -v $(pwd)/cqbot/:/root/ jesse2061/cqbot-swoole