#!/bin/sh
source "./conf.txt"

for((i=0;i<${#ip[*]};i++))
do
    echo "update :" ${ip[$i]}
    ./update_machine.sh $i
done
