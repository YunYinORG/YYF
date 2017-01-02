#!/usr/bin/env bash
:;PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )";
:<<":START_MSG"

::;##################################################
::;#### the following is BATCH scripts for Windows
::;##################################################

@ECHO OFF

CLS

CD /D %~dp0

SETLOCAL enabledelayedexpansion

FOR /F "tokens=* USEBACKQ" %%F IN (`CHCP`) DO for %%A in (%%~F) do set encoding=%%A

COLOR B

CALL :START_MSG

CALL :CHECK_CONIFG

CALL :CLEAN_TEMP

COLOR E

:DISPLAY_CHOICE

ECHO.
ECHO.select which development environment you want to use?
ECHO.  1) Use virtual Machine with vagrant;
ECHO.  2) Use local development (with PHP);
ECHO.  5) generate yaf intelligence editor tips;
ECHO.  0) Exit (in Server or Manual);
ECHO.
SET /P CHOICE=Input your choice (default[ENTER] is 1):

IF "%CHOICE%"=="" SET CHOICE=1

IF %CHOICE%==1 (
    COLOR B
    CALL :INIT_VAGRANTFILE
    CALL :CHECK_INSTALL
    CALL :INIT_BAT_SCRIPT
    COLOR A
    CALL :RESTART
)ELSE IF %CHOICE%==2 (
    CALL :INIT_SERVER_BATCH
    CALL :START_PHP_SERVER 
)ELSE IF %CHOICE%==5 (
    CALL :GENERATE_YAF
)ELSE IF %CHOICE%==0 (
    ECHO.Exit Development Environment Initialization.
)ELSE GOTO DISPLAY_CHOICE

PAUSE

GOTO :EOF

::;##################################################
::;#### the following is BATCH fuctions
::;##################################################

::;check secret config

:CHECK_CONIFG

IF NOT EXIST conf\secret.product.ini (
    COPY conf\secret.common.ini conf\secret.product.ini && ECHO.copy secret.common.ini  to secret.product.ini
)
GOTO :EOF


::;clear the temp files

:CLEAN_TEMP

IF NOT EXIST runtime  MKDIR runtime 
IF EXIST runtime\cache (RMDIR /s/q runtime\cache) 
IF EXIST runtime\kv (RMDIR /s/q runtime\kv) 
IF EXIST runtime\log (RMDIR /s/q runtime\log) 

ECHO runtime folders have been cleaned up.

GOTO :EOF


::;create the shutcut scripts

:INIT_BAT_SCRIPT


ECHO @ECHO OFF >start.cmd
ECHO.CD /D "%~dp0" >>start.cmd
ECHO.vagrant up >>start.cmd
ECHO.PAUSE >>start.cmd
ECHO.
ECHO          ------------------------------------------------------------

ECHO           [start.cmd] shortcut is created, to quickly STARTUP the VM

ECHO @ECHO OFF >stop.cmd
ECHO.CD /D "%~dp0" >>stop.cmd
ECHO.vagrant halt >>stop.cmd
ECHO.PAUSE >>stop.cmd

ECHO          ------------------------------------------------------------

ECHO           [stop.cmd] shortcut is created, to quickly SHUTDOWN the VM
ECHO          ------------------------------------------------------------

ECHO.
IF EXIST ".vagrant"  vagrant halt

GOTO :EOF


::;create local php server batch

:INIT_SERVER_BATCH
CALL :IF_EXIST php.exe && SET PHP_PATH=php&& GOTO CREATE_SERVER_CMD

:READ_PHP_PATH
ECHO.CAN NOT find the php.exe in path!
SET /P PHP_PATH=Input the PATH of the PHP (just drag it here):

IF EXIST "%PHP_PATH%/php.exe" (SET PHP_PATH="%PHP_PATH%\php.exe"
)ELSE IF EXIST "%PHP_PATH%" (SET PHP_PATH="%PHP_PATH%"
)ELSE GOTO READ_PHP_PATH

:CREATE_SERVER_CMD
ECHO.@ECHO OFF>server.cmd
ECHO.%PHP_PATH% -S 0.0.0.0:1122 -t "%~dp0public">>server.cmd
ECHO.
ECHO          ------------------------------------------------------------

ECHO           the  'server.cmd'  is created, to quickly start dev server!

ECHO          ------------------------------------------------------------
ECHO.
GOTO :EOF


::;install virtual box
:INSTALL_VIRTUAL_BOX
SET VBOX_URL=http://download.virtualbox.org/virtualbox/5.1.4/VirtualBox-5.1.4-110228-Win.exe
SET VBOX_PATH="%~dp0runtime\virtualbox.exe"
IF NOT EXIST "%VBOX_PATH%" (
ECHO. 
ECHO.Downloading VirtualBox from %VBOX_URL%
ECHO.Savig to %VBOX_PATH% ...
POWERSHELL -Command "Import-Module BitsTransfer;Start-BitsTransfer %VBOX_URL% %VBOX_PATH%"
ECHO.Done.
)
ECHO.Click [next] to finish the installation
"%VBOX_PATH%"
GOTO :CHECK_VBOX


::;install vagrant
:INSTALL_VAGRANT
SET VAGRANT_URL=https://releases.hashicorp.com/vagrant/1.8.5/vagrant_1.8.5.msi
ECHO.
ECHO.Install vagrant from %VAGRANT_URL%
msiexec /i %VAGRANT_URL% /log "%~dp0runtime\install.log"
ECHO.Done.

GOTO :EOF


::;CHECK the virtual box and vagrant is EXISTs?=
:CHECK_INSTALL

:CHECK_VBOX
CALL :IF_EXIST VBoxManage.exe && GOTO CHECK_VAGRANT
IF EXIST "%ProgramFiles%\Oracle\VirtualBox\VBoxManage.exe" (GOTO CHECK_VAGRANT)
IF EXIST "%ProgramFiles(x86)%\Oracle\VirtualBox\VBoxManage.exe" (GOTO CHECK_VAGRANT)
IF EXIST "%VBOX_MSI_INSTALL_PATH%\VBoxManage.exe" (GOTO CHECK_VAGRANT)
ECHO.
ECHO.virtual box is not installed press any key to install (or CTRL + C to exit)
PAUSE
CALL :INSTALL_VIRTUAL_BOX

:CHECK_VAGRANT
CALL :IF_EXIST vagrant.exe && GOTO :EOF
IF EXIST "%SYSTEMDRIVE%\HashiCorp\Vagrant\bin\vagrant.exe" (GOTO :EOF)
ECHO.
ECHO.vagrant is not installed press any key to install (or CTRL + C to exit)
PAUSE
CALL :INSTALL_VAGRANT

GOTO :EOF


::;Start local php server

:START_PHP_SERVER

ECHO.start the local PHP server...

COLOR A

%PHP_PATH% -S 0.0.0.0:1122 -t "%~dp0public"

GOTO :EOF

::; heredoc hack

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

::; command EXISTs or not

:IF_EXIST
SETLOCAL &PATH   %PATH% ; %~dp0 ; %cd%
if   "%~$PATH:1" == ""   exit   /b   1
exit   /b  0
GOTO :EOF



::;##############################################################
::;####the following is COMMON fuctions (both for bash and batch)
::;##############################################################

::;show start message

:START_MSG

:;START_MSG(){ 
echo ===============================================================================
echo -------------------------------------------------------------------------------

echo __________________________ INITIALIZE YYF ENVIRONMENT _________________________
echo ...............................................................................
echo ----------- *_* goto [https://yyf.newfuture.cc/setup/] for help *_* -----------

echo _______________________________________________________________________________
echo ===============================================================================

:;}
:<<":INIT_VAGRANTFILE"

GOTO :EOF


::;#create the Vagrantfile

:INIT_VAGRANTFILE
 
:;INIT_VAGRANTFILE(){ 

echo CREATE the Vagrantfile

:;FILE=Vagrantfile;cat>$FILE<<'REM'
call :heredoc vagrantconfig >Vagrantfile && goto :VAGRANT_FILE
# -*- mode: ruby -*-
# vi: set ft=ruby :
########## YYF vagrant Virtual Machine Config ###########
vm_memory   = 512  # set the memory of virtual machine
web_port    = 0    # the local port map to the web server,like 8080 or 80
ssh_port    = 0    # the local port map to the  ssh port like 2333
static_ip   = "192.168.23.33" # set the static ip of the virtual machine 
use_pub_net = false # use the public network or not
VERSION     = "2.4" # current version
box_name    = "newfuture/YYF"
# the shell script in the virtual machine to init the VM at the fisrt time
init_shell  = %q{
cd /vagrant/;
ls tests/init_*.sh 2>/dev/null|xargs -n1 bash;
if [ -f 'tests/yyf.sql' ];then
  sed '/^\/\*MYSQL/d;/MYSQL\*\//d' tests/yyf.sql|mysql -uroot;
  sed '/^\/\*SQLITE/d;/SQLITE\*\//d' tests/yyf.sql|sqlite3 runtime/yyf.db;
fi;
if [ -f 'tests/mysql.sql' ];then mysql -uroot mysql<tests/mysql.sql;fi;
}
#########################################################

Vagrant.configure(2) do |config|
  config.vm.box = box_name
  config.vm.box_check_update = false
  config.vm.define "YYF" do |yyf|
  end
  config.vm.synced_folder ".", "/vagrant", :mount_options =>["dmode=777,fmode=666"]
  ### APPLY THE NETWORK CONFIG ###
  if web_port>0
    config.vm.network "forwarded_port", guest: 80, host: web_port, auto_correct: true
  end
  if ssh_port>0
    config.vm.network "forwarded_port", guest: 22, host: 2222, id: "ssh", disabled: true
    config.vm.network "forwarded_port", guest: 22, host: ssh_port, auto_correct: true
  end
  if static_ip.empty?  
    webhost=(web_port==80) ? "127.0.0.1" : "127.0.0.1:#{web_port}"
    sshhost="127.0.0.1:#{ssh_port}"
  else #static IP
    config.vm.network "private_network", ip: static_ip, auto_config: true
    webhost=static_ip
    sshhost=static_ip
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
  message<<"\n  For Windows: SSH vagrant@#{sshhost} (password:vagrant)"
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
:<<":GENERATE_YAF"

GOTO :EOF


::;#generate yaf intelligence tips file

:GENERATE_YAF
:;GENERATE_YAF(){

echo generate YAF intelligence tips file to '.yaf.php'

:;FILE='.yaf.php';cat>$FILE<<'REM'
call :heredoc yaf_php >.yaf.php && goto :YAF_PHP
<?php
define('YAF_ERR_STARTUP_FAILED', 512);
define('YAF_ERR_ROUTE_FAILED', 513);
define('YAF_ERR_DISPATCH_FAILED', 514);
define('YAF_ERR_AUTOLOAD_FAILED', 520);
define('YAF_ERR_NOTFOUND_MODULE', 515);
define('YAF_ERR_NOTFOUND_CONTROLLER', 516);
define('YAF_ERR_NOTFOUND_ACTION', 517);
define('YAF_ERR_NOTFOUND_VIEW', 518);
define('YAF_ERR_CALL_FAILED', 519);
define('YAF_ERR_TYPE_ERROR', 521);
final class Yaf_Application
{
	protected $config;
	protected $dispatcher;
	static protected $_app;
	protected $_modules;
	protected $_running;
	protected $_environ;
	protected $_err_no;
	protected $_err_msg;
	public static function app(){}
	public function bootstrap(){}
	public function run(){}
	public function clearLastError(){}
	public function __construct($config, $environ = null){}
	public function execute(callable $entry, $parameter = null){}
	public function environ(){}
	public function getAppDirectory(){}
	public function getConfig(){}
	public function getDispatcher(){}
	public function getLastErrorMsg(){}
	public function getLastErrorNo(){}
	public function getModules(){}
	public function setAppDirectory($directory){}
	private function __clone(){}
	public function __destruct(){}
	private function __sleep(){}
	private function __wakeup(){}
}
abstract class Yaf_Bootstrap_Abstract{}
final class Yaf_Dispatcher
{
	protected $_request;
	protected $_view;
	protected $_router;
	static protected $_instance;
	protected $_auto_render;
	protected $_return_response;
	protected $_instantly_flush;
	protected $_default_module;
	protected $_default_controller;
	protected $_default_action;
	protected $_plugins;
	public function disableView(){}
	public function enableView(){}
	public function initView($tpl_dir, $options = null){}
	public function setView(Yaf_View_Interface $view){}
	public function setRequest(Yaf_Request_Abstract $request){}
	public function getApplication(){}
	public function getRouter(){}
	public function getRequest(){}
	public function setErrorHandler(callable $callback, $error_type = 32767){}
	public function setDefaultModule($module){}
	public function setDefaultController($controller){}
	public function setDefaultAction($action){}
	public function returnResponse($flag){}
	public function autoRender($flag){}
	public function flushInstantly($flag){}
	public static function getInstance(){}
	public function dispatch(Yaf_Request_Abstract $request){}
	public function throwException($flag){}
	public function catchException($flag){}
	public function registerPlugin(Yaf_Plugin_Abstract $plugin){}
	private function __construct(){}
	private function __clone(){}
	public function __destruct(){}
	private function __sleep(){}
	private function __wakeup(){}
}
abstract class Yaf_Config_Abstract implements Iterator, Countable, ArrayAccess
{
	protected $_config;
	protected $_readonly;
	abstract function get($name);
	abstract function set($name, $value);
	abstract function readonly();
	abstract function toArray();
	public function __get($name){}

}
abstract class Yaf_Controller_Abstract
{
	public $actions;
	protected $_module;
	protected $_name;
	protected $_request;
	protected $_response;
	protected $_invoke_args;
	protected $_view;
	protected function render($action_name, $var_array = array()){}
	protected function display($action_name, $var_array = array()){}
	public function getRequest(){}
	public function getResponse(){}
	public function getModuleName(){}
	public function initView(){}
	public function getView(){}
	public function setViewPath($path){}
	public function getViewPath(){}
	public function forward($module, $controller = null, $action = null, $parameters = null){}
	public function redirect($url){}
	public function getInvokeArgs(){}
	public function getInvokeArg($name){}
	final public function __construct(){}
	final public function __clone(){}
}
abstract class Yaf_Action_Abstract
{
	protected $_controller;
	public function getController(){}
	abstract public function execute();
}
interface Yaf_View_Interface
{
	public function assign($name, $value = null);
	public function display($tpl, $var_array = array());
	public function render($tpl, $var_array = array());
	public function setScriptPath($tpl_dir);
	public function getScriptPath();
}
final class Yaf_Loader
{
	protected $_library;
	protected $_global_library;
	static protected $_instance;
	private function __construct(){}
	private function __clone(){}
	public function __destruct(){}
	private function __sleep(){}
	private function __wakeup(){}
	public function autoload($class){}
	public static function getInstance($library = null, $global = null){}
	public function registerLocalNamespace($namespace){}
	public function getLocalNamespace(){}
	public function clearLocalNamespace(){}
	public function isLocalName($class_name){}
	public static function import($file){}
	public function setLibraryPath($library, $global = false){}
	public function getLibraryPath($global = false){}
}
abstract class Yaf_Plugin_Abstract
{
	public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){}
	public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){}
	public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){}
	public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){}
	public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){}
	public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){}
}
final class Yaf_Registry
{
	static protected $_instance;
	protected $_entries;
	private function __construct(){}
	private function __clone(){}
	public static function get($name){}
	public static function has($name){}
	public static function set($name, $value){}
	public static function del($name){}
}
abstract class Yaf_Request_Abstract
{
	public $module;
	public $controller;
	public $action;
	public $method;
	protected $params;
	protected $language;
	protected $_exception;
	protected $_base_uri;
	protected $uri;
	protected $dispatched;
	protected $routed;
	public function isGet(){}
	public function isPost(){}
	public function isPut(){}
	public function isHead(){}
	public function isOptions(){}
	public function isCli(){}
	public function isXmlHttpRequest(){}
	public function getServer($name, $default = null){}
	public function getEnv($name, $default = null){}
	public function setParam($name, $value){}
	public function getParam($name, $default = null){}
	public function getParams(){}
	public function getException(){}
	public function getModuleName(){}
	public function getControllerName(){}
	public function getActionName(){}
	public function setModuleName($name){}
	public function setControllerName($name){}
	public function setActionName($name){}
	public function getMethod(){}
	public function getLanguage(){}
	public function setBaseUri($baseuri){}
	public function getBaseUri(){}
	public function getRequestUri(){}
	public function setRequestUri($uri){}
	public function isDispatched(){}
	public function setDispatched(){}
	public function isRouted(){}
	public function setRouted(){}
}
abstract class Yaf_Response_Abstract
{
	protected $_header;
	protected $_body;
	protected $_sendheader=0;
	protected $_response_code;
	public $DEFAULT_BODY='content';
	public function __construct(){}
	public function __destruct(){}
	private function __clone(){}
	public function __toString(){}
	public function setBody($body, $name = NULL){}
	public function getBody($name = NULL){}
	public function appendBody($body, $name = null){}
	public function prependBody($body, $name = null){}
	public function clearBody(){}
	public function getHeader(){}
	public function setAllHeaders(){}
	public function setHeader(){}
	public function clearHeaders(){}
	public function setRedirect($url){}
	public function response(){}

}
interface Yaf_Route_Interface
{
	public function route(Yaf_Request_Abstract $request);
	public function assemble(array $info, array $query = null);
}
class Yaf_Route_Static implements Yaf_Route_Interface
{
	public function match(){}
	public function route(Yaf_Request_Abstract $request){}
	public function assemble(array $info, array $query = null){}

}
final class Yaf_Router
{
	protected $_routes;
	protected $_current;
	public function __construct(){}
	public function addRoute($name, Yaf_Route_Interface $route){}
	public function addConfig($config){}
	public function route(Yaf_Request_Abstract $request){}
	public function getRoute($name){}
	public function getRoutes(){}
	public function getCurrentRoute(){}
}
final class Yaf_Session implements Iterator, ArrayAccess, Countable
{
	private function __construct(){}
	private function __clone(){}
	private function __sleep(){}
	private function __wakeup(){}
	public static function getInstance(){}
	public function start(){}
	public function __destruct(){}
	public function __isset($name){}
	public function get($name){}
	public function set($name, $value){}
	public function del($name){}
	public function count(){}
	public function rewind(){}
	public function current(){}
	public function next(){}
	public function valid(){}
	public function key(){}
	public function offsetUnset($name){}
	public function offsetExists($name){}
	public function offsetGet($name){}
	public function offsetSet($name, $value){}
	public function __get($name){}
	public function __set($name, $value){}

}
class Yaf_Exception extends Exception
{
	protected $message;
	protected $code;
	protected $previous;
}
final class Yaf_Config_Ini extends Yaf_Config_Abstract
{
	public function __construct($filename, $section = null){}
	public function __isset($name){}
	public function set($name, $value){}
	public function count(){}
	public function rewind(){}
	public function current(){}
	public function next(){}
	public function valid(){}
	public function key(){}
	public function offsetUnset($name){}
	public function offsetExists($name){}
	public function offsetSet($name, $value){}
	public function offsetGet($name){}
	public function get($name){}
	public function __get($name){}
	public function __set($name, $value){}
	public function toArray(){}
	public function readonly(){}
}
final class Yaf_Config_Simple extends Yaf_Config_Abstract
{
	public function __construct($config, $readonly = null){}
	public function __isset($name){}
	public function set($name, $value){}
	public function count(){}
	public function rewind(){}
	public function current(){}
	public function next(){}
	public function valid(){}
	public function key(){}
	public function offsetUnset($name){}
	public function offsetExists($name){}
	public function offsetGet($name){}
	public function get($name){}
	public function offsetSet($name, $value){}
	public function __get($name){}
	public function __set($name, $value){}
	public function toArray(){}
	public function readonly(){}
}
class Yaf_Request_Http extends Yaf_Request_Abstract
{
	const SCHEME_HTTP='http';
	const SCHEME_HTTPS='https';
	public function __construct($request_uri = null, $base_uri = null){}
	public function getQuery($name = null, $default = null){}
	public function getRequest($name = null){}
	public function getPost($name = null, $default = null){}
	public function getCookie($name = null){}
	public function getFiles($name = null){}
	public function isXmlHttpRequest(){}
	public function get($name, $default = null){}
	private function __clone(){}
}
class Yaf_Request_Simple extends Yaf_Request_Abstract
{
	const SCHEME_HTTP='http';
	const SCHEME_HTTPS='https';
	public function __construct($method = null, $module = null, $controller = null, $action = null, $parameters = null){}
	public function getQuery($name = null){}
	public function getRequest($name = null){}
	public function getPost($name = null){}
	public function getCookie($name = null){}
	public function getFiles($name = null){}
	public function isXmlHttpRequest(){}
	public function get($name, $default = null){}
	private function __clone(){}
}
class Yaf_View_Simple implements Yaf_View_Interface
{
	protected $_tpl_vars;
	protected $_tpl_dir;
	protected $_options;
	public function __construct($tpl_dir, $options){}
	public function __isset($name){}
	public function __get($name){}
	public function get($name){}
	public function __set($name, $value){}
	public function clear($name){}
	public function assign($name, $value = null){}
	public function assignRef($name, $value){}
	public function display($tpl, $var_array = array()){}
	public function render($tpl, $var_array = array()){}
	public function setScriptPath($tpl_dir){}
	public function getScriptPath(){}
}
class Yaf_Response_Cli extends Yaf_Response_Abstract{}
class Yaf_Response_Http extends Yaf_Response_Abstract
{
	protected $_sendheader=1;
	protected $_response_code=200;
}
final class Yaf_Route_Map implements Yaf_Route_Interface
{
	protected $_ctl_router=0;
	protected $_delimeter;
	public function __construct($controller_prefer = 0, $delim = ''){}
	public function route(Yaf_Request_Abstract $request){}
	public function assemble(array $info, array $query = null){}
}
final class Yaf_Route_Regex implements Yaf_Route_Interface
{
	protected $_route;
	protected $_default;
	protected $_maps;
	protected $_verify;
	public function __construct($match, $route, $map, $verify){}
	public function route(Yaf_Request_Abstract $request){}
	public function assemble(array $info, array $query = null){}
}
final class Yaf_Route_Rewrite implements Yaf_Route_Interface
{
	protected $_route;
	protected $_default;
	protected $_verify;
	public function __construct($match, $route, $verify){}
	public function route(Yaf_Request_Abstract $request){}
	public function assemble(array $info, array $query = null){}
}
final class Yaf_Route_Simple implements Yaf_Route_Interface
{
	protected $module;
	protected $controller;
	protected $action;
	public function __construct($module, $controller, $action){}
	public function route(Yaf_Request_Abstract $request){}
	public function assemble(array $info, array $query = null){}
}
final class Yaf_Route_Supervar implements Yaf_Route_Interface
{
	protected $_var_name;public function __construct($var){}
	public function route(Yaf_Request_Abstract $request){}
	public function assemble(array $info, array $query = null){}
}
class Yaf_Exception_StartupError extends Yaf_Exception{}
class Yaf_Exception_RouterFailed extends Yaf_Exception{}
class Yaf_Exception_DispatchFailed extends Yaf_Exception{}
class Yaf_Exception_LoadFailed extends Yaf_Exception{}
class Yaf_Exception_TypeError extends Yaf_Exception{}
class Yaf_Exception_LoadFailed_Module extends Yaf_Exception_LoadFailed{}
class Yaf_Exception_LoadFailed_Controller extends Yaf_Exception_LoadFailed{}
class Yaf_Exception_LoadFailed_Action extends Yaf_Exception_LoadFailed{}
class Yaf_Exception_LoadFailed_View extends Yaf_Exception_LoadFailed{}
:YAF_PHP
REM
:;sed -i.temp -e '1d;$d' $FILE && rm $FILE.temp
:;}
:<<":RESTART"

GOTO :EOF


::;#restart and  init the VM

:RESTART

:;RESTART(){ 

echo -------------------------------------------------------------------------------

echo =================== init the environment of virtual machine ===================

echo -------------------------------------------------------------------------------

vagrant box update

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
if [ ! -d "runtime" ]; then
  mkdir runtime;
fi;
folders=("cache" "kv" "log");
for f in "${folders[@]}"; do  
  if [ -d "runtime/"$f ]; then
    rm -rf "runtime/"$f;
  fi
done;
chmod 777 runtime;
echo "runtime folders have been cleaned up !" ;
} 


INIT_BASH_SCRIPT(){ 

cat>start.cmd<<CMD
#!/bin/bash
cd "$PROJECT_DIR"
vagrant up
CMD
chmod +x start.cmd
echo " "
echo "          ------------------------------------------------------------"
echo "          the 'start.cmd' script is created, to quickly STARTUP the VM"

cat>stop.cmd<<CMD
#!/bin/bash
cd "$PROJECT_DIR"
vagrant halt
CMD
chmod +x stop.cmd;
echo "          ------------------------------------------------------------"
echo "          the 'stop.cmd' script is created, to quickly SHUTDOWN the VM"
echo "          ------------------------------------------------------------"
echo " "
if [ -d ".vagrant" ]; then
  vagrant halt;
fi;
}


INSTALL_YAF(){
INSTALL_URL="https://yyf.newfuture.cc/assets/code/yaf${1}.sh"
echo ""
echo "Install YAF from $INSTALL_URL " 
curl -#SL $INSTALL_URL |bash
}


INIT_SERVER_BASH(){

PHP_PATH=${PHP_PATH:="php"}
while [ -z $(command -v "$PHP_PATH" 2>/dev/null) ]; do
 echo "${PHP_PATH} in NOT EXIST command";
 echo -n "Input the PHP path:";
 read PHP_PATH
done;

YAF_MODULES=$("$PHP_PATH" -m|grep -c -w "yaf")
if [ $YAF_MODULES -eq 0 ] ; then
    echo "Yaf extension NOT EXIST!";
    export PHP_PATH="$PHP_PATH"
    if [ "$(uname)" != "Darwin" ]; then INSTALL_YAF ".dev" ;fi;
else
    echo "Yaf has been installed";
fi;

echo "\"${PHP_PATH}\" -S 0.0.0.0:1122 -t \"${PROJECT_DIR}/public/\"">'server.cmd'
chmod +x server.cmd
echo " "
echo "          ------------------------------------------------------------"
echo "           the  'server.cmd'  is created, to quickly start dev server!"
echo "          ------------------------------------------------------------"
echo " "
}

START_PHP_SERVER(){
  echo "start the local PHP server..."
  bash ./server.cmd;
}


DISPLAY_CHOICE(){

if [ "$(uname)" == "Darwin" ]; then 
#MAC

cat <<'EOF'

select which environment you want to use?
  1) Use virtual Machine with vagrant;
  2) Use php server (local development);
  0) Exit (Manual);

EOF

else # LINUX

cat <<'EOF'

select which environment you want to use?
  1) Use virtual Machine with vagrant;
  2) Use php server (local development);
  3) install yaf with DEV environ (local);
  4) install yaf with PRODUCT environ (server);
  5) generate yaf intelligence editor tips;  
  0) Exit (Manual);

EOF

fi;

echo -n "Input your choice (default[ENTER] is 1):";
read CHOICE;
if  [ ! -n "$CHOICE" ] ;then
 CHOICE=1;
fi;
echo ""
case "$CHOICE" in
"1") INIT_BASH_SCRIPT;
   INIT_VAGRANTFILE;
   RESTART;
   ;;
"2") INIT_SERVER_BASH;
   START_PHP_SERVER;
   ;;
"3") INSTALL_YAF ".dev";
   ;;
"4") INSTALL_YAF;
   ;;
"5") GENERATE_YAF;
   ;;
"0") echo "Exit Development Environment Initialization." ;
   exit;
   ;;
*) DISPLAY_CHOICE;
   ;;
esac
}

:;##################################################
:;#### the following is BASH commond to init
:;##################################################
cd $PROJECT_DIR;
clear;

START_MSG;
CHECK_CONIFG;
CLEAN_TEMP;

DISPLAY_CHOICE;
exit
