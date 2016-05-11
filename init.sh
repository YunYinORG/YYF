#!/bin/bash

#clean temp
echo '清理和重建缓存文件'
if [ -d "temp" ]; then
  rm -r temp
fi
mkdir temp temp/log temp/kv temp/cache 
chmod 777 -R temp

echo "创建快速启动start.cmd"
echo $'#!/bin/bash\nvagrant up\n' > start.cmd
chmod +x start.cmd
echo '创建快速关闭stop.cmd'
echo $'#!/bin/bash\nvagrant halt\n' > stop.cmd
chmod +x stop.cmd

echo "重启虚拟机"
vagrant halt
vagrant up --provision