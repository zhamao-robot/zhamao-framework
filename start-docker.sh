#!/bin/bash

sudo docker run -it --rm --name my-running-script -v $(pwd)/cqbot/:/root/ cqbot