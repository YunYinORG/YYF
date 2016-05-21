# -*- mode: ruby -*-
# vi: set ft=ruby :
##### YYF vagrant 虚拟机开发环境配置 ########
vm_memory   = 512   #为虚拟机分配内存，可根据本机增大如1024
web_port    = 8888  #web端口，如果主机映射端口被占用换做其他
ssh_port    = 2333  #ssh端口
static_ip   = "192.168.23.33" #调试用静态ip，只有主机能访问
use_pub_net = false #是否启用共享的桥架网络
VERSION     = "2.0" #版本
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
  # 使用静态IP
  if !static_ip.empty?
    config.vm.network "private_network", ip: static_ip
    webhost=static_ip
  else
      webhost=(local_web_port==80) ? "127.0.0.1" : "127.0.0.1:#{local_web_port}"
  end
  if use_pub_net
      config.vm.network "public_network"
  end
  ### 虚拟机配置 ####
  config.vm.provider "virtualbox" do |vb|# virtualbox
    vb.memory = vm_memory
  end
  config.vm.provision "shell" ,inline: init_shell

  message = "\n\n\n\n\t\t\tYYF 虚拟机环境已启动 V#{VERSION}"#启动显示提示
  message<<"\nWEB测试地址：http://#{webhost}/ 可显示和调试web界面"
  message<<"\nMySQL管理：http://#{webhost}/adminer 查看修改数据库"
  message<<"\n进入虚机方法："
  message<<"\n  widnows用putty：ssh vagrant@127.0.0.1:#{ssh_port} 密码:vagrant"
  message<<"\n  Linux或Mac用户：此目录输入命令 vagrant ssh\n"
  message<<"\n常用命令: \t 初始化虚机环境[vagrant provision]"
  message<<"\n开机[vagrant up] 关机[vagrant halt] 重启[vagrant reload]\n"
  message<<"\n\t\t（^_^）Enjoy your coding（^_^）\n \n \n"
  config.vm.post_up_message=message
end