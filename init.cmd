:;workdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )";
:<<":START_MSG"

::;##################################################
::;#### the following is BATCH scripts for Windows
::;##################################################

@ECHO OFF & SETLOCAL enabledelayedexpansion & CD /D %~dp0

FOR /F "tokens=* USEBACKQ" %%F IN (`CHCP`) DO for %%A in (%%~F) do set encoding=%%A

CHCP 65001>nul  & COLOR B

CALL :START_MSG

CALL :CHECK_CONIFG

CALL :CLEAN_TEMP

COLOR E

SET restart=YES

SET /P restart= Init the Virtual Machine ? (input N[O] to exit; press ENTER to continue):

IF /I %restart%==NO (GOTO :EOF)ELSE IF /I %restart%==N GOTO :EOF

CALL :INIT_BAT_SCRIPT

CALL :INIT_VAGRANTFILE


CHCP %encoding%>nul

COLOR A

CALL :RESTART

PAUSE

GOTO :EOF

::;##################################################
::;#### the following is BATCH fuctions
::;##################################################

::;check secret config

:CHECK_CONIFG

IF NOT EXIST conf\secret.product.ini (
    COPY conf\secret.common.ini conf\secret.product.ini && ECHO copy secret.common.ini  to secret.product.ini
)
GOTO :EOF


::;clear the temp files

:CLEAN_TEMP

IF NOT EXIST temp  MKDIR temp 
IF EXIST temp\cache (RMDIR /s/q temp\cache) 
IF EXIST temp\kv (RMDIR /s/q temp\kv) 
IF EXIST temp\log (RMDIR /s/q temp\log) 

MKDIR temp\cache temp\kv temp\log 

ECHO Temp folders have been cleaned up.

GOTO :EOF


::;create the shutcut scripts

:INIT_BAT_SCRIPT

ECHO.Create [start.cmd], to quickly STARTUP the VM

ECHO @ECHO OFF >start.cmd
ECHO.CD /D "%~dp0" >>start.cmd
ECHO.vagrant up >>start.cmd
ECHO.PAUSE >>start.cmd

ECHO.Create [stop.cmd], to quickly SHUTDOWN the VM

ECHO @ECHO OFF >stop.cmd
ECHO.CD /D "%~dp0" >>stop.cmd
ECHO.vagrant halt >>stop.cmd
ECHO.PAUSE >>stop.cmd

GOTO :EOF


:heredoc <uniqueIDX>

set go=
for /f "delims=" %%A in ('findstr /n "^" "%~f0"') do (
    set "line=%%A" && set "line=!line:*:=!" 
    if defined go (if #!line:~1!==#!go::=! (goto :EOF) else echo(!line!)
    if "!line:~0,13!"=="call :heredoc" (
        for /f "tokens=3 delims=>^ " %%i in ("!line!") do (
            if #%%i==#%1 (for /f "tokens=2 delims=&" %%I in ("!line!") do (
                for /f "tokens=2" %%x in ("%%I") do set "go=%%x")
            )
    ))
)
GOTO :EOF


::;##############################################################
::;####the following is COMMON fuctions (both for bash and batch)
::;##############################################################

::;show start message

:START_MSG

:;START_MSG(){ 
echo _______________________________________________________________________________
echo ===============================================================================
echo ========================== INITIALIZE YYF ENVIRONMENT =========================
echo ===============================================================================

:;}
:<<":INIT_VAGRANTFILE"

GOTO :EOF


::;create the Vagrantfile

:INIT_VAGRANTFILE
 
:;INIT_VAGRANTFILE(){ 

echo CREATE the Vagrantfile

:;FILE=Vagrantfile;cat>$FILE<<'REM'
call :heredoc vagrantconfig >Vagrantfile && GOTO :VAGRANT_FILE
# -*- mode: ruby -*-
# vi: set ft=ruby :
########## YYF vagrant Virtual Machine Config ###########
vm_memory   = 512  # set the memory of virtual machine
web_port    = 0    # the local port map to the web server,like 8080 or 80
ssh_port    = 2333 # the local port map to the  ssh port like 2345
static_ip   = "192.168.23.33" # set the static ip of the virtual machine 
use_pub_net = false # use the public network or not
VERSION     = "2.3" # current version
init_shell  = "echo $(date)>InitTime.txt" # the shell script in the virtual machine to init the VM at the fisrt time
box_name    = "newfuture/YYF"
#########################################################

Vagrant.configure(2) do |config|
  config.vm.box = box_name
  config.vm.define "YYF" do |yyf|
  end
  config.vm.synced_folder ".", "/vagrant", :mount_options =>["dmode=777,fmode=777"]
  ### APPLY THE NETWORK CONFIG ###
  if web_port>0
    config.vm.network "forwarded_port", guest: 80, host: web_port
  end
  if ssh_port>0
    config.vm.network "forwarded_port", guest: 22, host: ssh_port
  end
  if static_ip.empty?  
    webhost=(web_port==80) ? "127.0.0.1" : "127.0.0.1:#{web_port}"
  else #static IP
    config.vm.network "private_network", ip: static_ip
    webhost=static_ip
  end
  if use_pub_net
      config.vm.network "public_network"
  end
  ### CONFIG of the VM ####
  config.vm.provider "virtualbox" do |vb|# virtualbox
    vb.memory = vm_memory
  end
  config.vm.provision "shell" ,inline: init_shell
  
  message = "\n\n\n\n\t\t YYF V#{VERSION} Virtual Machine is RUNNING \n\n"
  message<<"="*70+"\n"
  message<<"\nWeb Server debug URL: http://#{webhost}/ "
  message<<"\nDB Management online: http://#{webhost}/adminer\n"
  message<<"-"*70+"\n"
  message<<"\nAccess to the virtual machine:"
  message<<"\n  For Windows: SSH vagrant@127.0.0.1:#{ssh_port} (password:vagrant)"
  message<<"\n  Linux / MAC: just use the command  [vagrant ssh]\n"
  message<<"-"*70
  message<<"\nCommon-use Commands:"
  message<<"\n open[vagrant up], shutdown[vagrant halt], reload[vagrant reload]\n"
  message<<"-"*70+"\n"
  message<<"\nSHORTCUT:     [./stop.cmd](shutdown)      [./start.cmd](startup)\n"
  message<<"="*70+"\n"
  message<<"\n\t\t(^_^) Enjoy your coding (^_^)\n \n "
  config.vm.post_up_message=message
end
:VAGRANT_FILE
REM
:;sed -i.temp -e '1d;$d' $FILE && rm $FILE.temp
:;}
:<<":RESTART"

GOTO :EOF


::;restart and  init the VM

:RESTART

:;RESTART(){ 

echo -------------------------------------------------------------------------------

echo =================== init the environment of virtual machine ===================

echo -------------------------------------------------------------------------------

vagrant halt

vagrant up --provision
 
:;}
:<<"::BASH_FUNCTIONS"

GOTO :EOF


:;##################################################
:;#### the following is BASH fuctions
:;##################################################
::BASH_FUNCTIONS

CHECK_CONIFG(){ 
if [ ! -f "conf/secret.product.ini" ]; then
  cp conf/secret.common.ini conf/secret.product.ini;
  echo "copy secret.common.ini  to secret.product.ini";
fi;
 
}


CLEAN_TEMP(){ 
if [ ! -d "temp" ]; then
  mkdir temp;
fi;
folders=("cache" "kv" "log");
for f in "${folders[@]}"; do  
  if [ -d "temp/"$f ]; then
    rm -r "temp/"$f;
  else
    mkdir "temp/"$f;
  fi
done;
chmod -R 755 temp;
echo "Temp folders have been cleaned up !" ;
} 


INIT_BASH_SCRIPT(){ 
echo "create '${workdir}/start.cmd', to quickly STARTUP the VM"
cat>start.cmd<<CMD
#!/bin/bash
cd "$workdir"
vagrant up
CMD
chmod +x start.cmd

echo "create '${workdir}/stop.cmd', to quickly SHUTDOWN the VM"
cat>stop.cmd<<CMD
#!/bin/bash
cd "$workdir"
vagrant halt
CMD
chmod +x stop.cmd;
}


:;##################################################
:;#### the following is BASH commond to init
:;##################################################
cd $workdir;
clear;

START_MSG;
CHECK_CONIFG;
CLEAN_TEMP;

echo -n "Init the Virtual Machine? (input N[O] to exit; press ENTER to continue):";
read restart;
restart=${restart:0:1};
if [ "$restart" == "n" ] || [ "$restart" == "N" ]; then
  exit;
fi;

INIT_BASH_SCRIPT;
INIT_VAGRANTFILE;
RESTART;

exit;
