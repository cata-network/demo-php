#!/bin/bash
#########################################################################
# Author: yaofei
# File Name: publish.sh
# Description: 上线正式环境的脚本
#########################################################################

rsync -avzP --delete ./ root@139.196.203.107:/var/www/html/videos-php
rsync -avzP --delete ./ root@139.224.31.162:/var/www/html/videos-php

