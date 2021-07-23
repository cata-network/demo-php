#!/bin/sh
#########################################################################
# Author: billczhang
# Created Time: Wed 12 Aug 2020 11:00:14 AM CST
# File Name: push.sh
# Description: 
#########################################################################
#step 1, add file
git add *
#step 2, commit
git commit -m "message author"
#step 3, push
git push origin master
