@ECHO off

ECHO 设置快捷启动：双击start.cmd可直接启动虚机环境
ECHO @echo off > start.cmd
ECHO echo 正在启动虚拟机环境... >> start.cmd
ECHO vagrant up>>start.cmd
ECHO pause>> start.cmd


ECHO 设置快速关闭：双击stop.cmd可直接启动虚机环境
ECHO @echo off > stop.cmd
ECHO echo 正在关闭虚拟机环境 >> stop.cmd
ECHO vagrant halt>>stop.cmd
ECHO pause >> stop.cmd

ECHO 清理临时目录
RD temp /s /q
mkdir temp
mkdir temp\cache
mkdir temp\kv
mkdir temp\log

ECHO 启动和配置虚机环境
vagrant halt
vagrant up --provision
pause