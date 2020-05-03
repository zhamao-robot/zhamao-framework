#!/bin/bash
if [ ! -d "/app/zhamao-framework/bin" ]; then
	cp -r /app/zhamao-framework-bak/* /app/zhamao-framework/
fi
php /app/zhamao-framework/bin/start
