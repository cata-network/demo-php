#!/bin/bash
#########################################################################
# Author: yaofei
# File Name: publish-dev.sh
# Description: 上线到测试环境的脚本
#########################################################################

rsync -avzP --delete ./ root@101.133.225.240:/var/www/html/videos-php

# 提交（测试），并强制更新服务器内容
# git add .
# git commit --amend -m 'test'
# git push origin master -f
# ssh root@212.64.58.86 "cd /var/www/html/cangpinyanjiu_php; git fetch --all; git reset --hard origin/master; chmod -R 777 docs/; chmod -R 777 access_token.php; chmod -R 777 jsapi_ticket.php;"

