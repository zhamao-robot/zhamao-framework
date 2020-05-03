#!/bin/bash

unzip master.zip
mv zhamao-framework-master/* zhamao-framework/
cd zhamao-framework
php bin/start
