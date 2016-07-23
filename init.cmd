:<<":START"
@ECHO off


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
echo ------------------------------------------------------------
echo init the virtual machine environment
echo ============================================================
vagrant halt
vagrant up --provision

:;exit
pause