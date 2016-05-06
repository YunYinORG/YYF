# -*- mode: ruby -*-
# vi: set ft=ruby :
#YYF vagrant 虚拟机开发环境自动部署

box_name     = "newfuture/yyf"
web_port     = 80    #web端口，如果主机映射端口被占用换做其他
ssh_port     = 2333  #ssh端口
static_ip    = "192.168.23.33" #ip
vm_memory    = 512   #为虚拟机分配内存，可根据本机增大如1024
show_window  = false #开机后是否显示窗口,如果要打开改为 true
message      = "\n\n\n\n\t\t\tYYF 虚拟机环境已启动 V1.0"#启动显示提示

#一下是具体配置，#号为注释内容
Vagrant.configure(2) do |config|
  config.vm.box = box_name
  ### 网络配置 ###
  if web_port>0
    config.vm.network "forwarded_port", guest: 80, host: web_port
  end
  if ssh_port>0
    config.vm.network "forwarded_port", guest: 22, host: ssh_port
  end
  # 使用静态
  if !static_ip.empty?
    config.vm.network "private_network", ip: static_ip
    webhost=static_ip
  else
      viewport=(local_web_port==80 ? "" : ":#{local_web_port}")
      webhost="127.0.0.1"<<viewport
  end
  # config.vm.network "public_network"

  ### 虚拟机配置 ####
  # virtualbox
  config.vm.provider "virtualbox" do |vb|
    vb.gui = show_window
    vb.memory = vm_memory
  end
  
  # httpd config
  config.vm.provision "http",type: "shell" ,inline: "sudo service httpd restart",run: "always",privileged:false

  message<<"\nMySQL在线管理：http://#{webhost}/adminer\t可实时修改查看数据库"
  message<<"\n进入虚机方式：ssh vagrant@127.0.0.1:#{ssh_port}\t密码:vagrant(不常用)\n"
  message<<"\n常用命令:"
  message<<"\n关机[vagrant halt] 重启[vagrant reload]" 
  message<<"\n"
  message<<"\n\t\t（^_^）Enjoy your coding（^_^）\n \n \n"
  config.vm.post_up_message=message
end