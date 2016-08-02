:<<":START"
@ECHO off
@chcp 65001

:START
echo ========================= YYF INIT =========================
echo cleaning up temp folders ...

:<<"::BASH"
GOTO :CMD



::BASH
#bash scripts
if [ -d "temp" ]; then
  rm -r temp
fi
mkdir temp temp/log temp/kv temp/cache 
chmod -R 777 temp

if [ ! -f "conf/secret.product.ini" ]; then
  cp conf/secret.common.ini conf/secret.product.ini
  echo "copy secret.common.ini  to secret.product.ini"
fi

echo "create [start.cmd], for starting the virtual machine"
echo $'#!/bin/bash\nvagrant up' > start.cmd
chmod +x start.cmd
echo "create [stop.cmd], for stoping the virtual machine"
echo $'#!/bin/bash\nvagrant halt' > stop.cmd
chmod +x stop.cmd
:<<":END"


:CMD
REM widnows batch scripts
RD temp /s /q
mkdir temp
mkdir temp\cache temp\kv temp\log

if not exist conf\secret.product.ini copy conf\secret.common.ini conf\secret.product.ini

ECHO create [start.cmd], for starting the virtual machine
(ECHO @echo off && ECHO.vagrant up && ECHO.pause) > start.cmd
ECHO create [stop.cmd], for stoping the virtual machine
(ECHO @echo off && ECHO.vagrant halt && ECHO.pause) > stop.cmd



:END

echo CREATE the Vagrantfile
:;FILE=Vagrantfile;cat>$FILE<<'REM'
call :heredoc VagrantCONIFG >Vagrantfile && goto VAGRANTFILE
# -*- mode: ruby -*-
# vi: set ft=ruby :
##### YYF vagrant 虚拟机开发环境配置 ########
vm_memory   = 512   #为虚拟机分配内存，可根据本机增大如1024
web_port    = 8888  #web端口，如果主机映射端口被占用换做其他
ssh_port    = 2333  #ssh端口
static_ip   = "192.168.23.33" #调试用静态ip，只有主机能访问
use_pub_net = false #是否启用共享的桥架网络
VERSION     = "2.3" #版本
init_shell  = "echo $(date)>InitTime.txt" #自动配置虚拟机环境，虚机中运行命令或者脚本
box_name    = "newfuture/YYF"
#########################################

Vagrant.configure(2) do |config|
  config.vm.box = box_name
  config.vm.synced_folder ".", "/vagrant", :mount_options =>["dmode=777,fmode=777"]
  ### 网络配置 ###
  if web_port>0
    config.vm.network "forwarded_port", guest: 80, host: web_port
  end
  if ssh_port>0
    config.vm.network "forwarded_port", guest: 22, host: ssh_port
  end
  if !static_ip.empty?  # 使用静态IP
    config.vm.network "private_network", ip: static_ip
    webhost=static_ip
  else
      webhost=(web_port==80) ? "127.0.0.1" : "127.0.0.1:#{web_port}"
  end
  if use_pub_net
      config.vm.network "public_network"
  end
  ### 虚拟机配置 ####
  config.vm.provider "virtualbox" do |vb|# virtualbox
    vb.memory = vm_memory
  end
  config.vm.provision "shell" ,inline: init_shell

  message = "\n\n\n\n\t\t   YYF V#{VERSION} 虚拟机环境已启动 \n"#启动显示提示
  message<<"\n服务器调试：http://#{webhost}/ 可显示和调试web界面"
  message<<"\n数据库管理：http://#{webhost}/adminer 查看修改数据库"
  message<<"\n进入虚机方法：(通常并不需要进入虚机)"
  message<<"\n  Windows 系统: SSH连[vagrant@127.0.0.1:#{ssh_port}]密码vagrant"
  message<<"\n  Linux/Mac OS：在当前目录输入命令 [vagrant ssh] 即可\n"
  message<<"\n常用命令: \t 再次初始化虚机[vagrant provision]"
  message<<"\n开机[vagrant up] 关机[vagrant halt] 重启[vagrant reload]\n"
  message<<"\n一键脚本:  ./stop.cmd (关闭虚机)   ./start.cmd (快速开机)\n"
  message<<"\n\t\t（^_^）Enjoy your coding（^_^）\n \n \n"
  config.vm.post_up_message=message
end
:VAGRANTFILE
REM
:;sed -i.temp -e '1d;$d' $FILE && rm $FILE.temp


echo ------------------------------------------------------------
echo init the virtual machine environment
echo ============================================================
vagrant halt
vagrant up --provision

:;exit
pause

goto :EOF
:heredoc <uniqueIDX>
setlocal enabledelayedexpansion
set go=
for /f "delims=" %%A in ('findstr /n "^" "%~f0"') do (
    set "line=%%A" && set "line=!line:*:=!"
    if defined go (if #!line:~1!==#!go::=! (goto :EOF) else echo(!line!)
    if "!line:~0,13!"=="call :heredoc" (for /f "tokens=3 delims=>^ " %%i in ("!line!") do (if #%%i==#%1 (for /f "tokens=2 delims=&" %%I in ("!line!") do (for /f "tokens=2" %%x in ("%%I") do set "go=%%x")))
))
goto :EOF
