#/bin/sh
#更新一个机器
source "./conf.txt"
if [ $# -ne 1 ]
then
    echo "please input machine id, like 0"
    exit
fi

#machine id
id=$1

#ip....
ip=${ip[$id]}
ssh_port=${ssh_port[$id]}
user=${user[$id]}
passwd=${passwd[$id]}
install_dir=${install_dir[$id]}

#shell 命令
cmd="./update.sh"
./run_remote.sh $ip $ssh_port $user $passwd "cd ${install_dir};$cmd" 
