<?php
namespace Debug;

class Assertion
{
    protected static $instance = null;
    protected static $assertion = null;

    public static function init(array $config)
    {
        if (!static::$instance) {
            $active = isset($config['active']) ? $config['active'] : true;
            $warning = isset($config['warning']) ? $config['warning'] : false;
            $bail = isset($config['bail']) ? $config['bail'] : true;
            static::$instance = new static($active, $warning, $bail);
        }
    }

    protected function __construct($active, $warning, $bail)
    {
        ob_start();
        if ($active) {
            if (version_compare(PHP_VERSION, '7.0.0', '>=')) { //for php7

                //判断环境
                if (-1 == ini_get('zend.assertions')) {
                    exit("调试环境，请开启php7的断言，以便更早发现问题！\n<br>(<u>在php.ini 中的设置 zend.assertions = 1 开启断言【推荐】</u>;或者在 conf/app.ini 中设置 assert.active = 0 关闭此警告【不推荐】。)\n<br>In development environment, please open assertion for php7 to debug ! \n<br> (set 'zend.assertions = 1' in [php.ini][recommended]; or set 'assert.active = 0' in [conf/app.ini] to ignore this [not recommender].)");
                }
                //PHP7配置
                ini_set('zend.assertions', 1);//开启断言
                ini_set('assert.exception', 0);//关闭断言异常
            } elseif (version_compare(PHP_VERSION, '5.4.8', '<')) {
                //低版本(php5.3)断言
                set_error_handler(array(__CLASS__, 'php53'), E_WARNING);
            }

            assert_options(ASSERT_ACTIVE, true);
            //断言错误回调
            assert_options(ASSERT_CALLBACK, array(__CLASS__, 'callback'));
        } else {
            assert_options(ASSERT_ACTIVE, false);
        }

        assert_options(ASSERT_QUIET_EVAL, false);//关闭在断言表达式求值时禁用error_reporting
        assert_options(ASSERT_WARNING, $warning);//为每个失败的断言产生一个 PHP 警告（warning)
        assert_options(ASSERT_BAIL, $bail);//在断言失败时中止执行
    }

    /**
     * 兼容PHP5.3的assert(只支持一个参数)
     * 参数表$number, $message, $file, $line, $context
     * 为了不影响恢复断言环境，此方法内避免使用任何局部参数或变量
     */
    public static function php53()
    {
        if (func_get_arg(1) !== 'assert() expects exactly 1 parameter, 2 given') {
            return false;  //非断言参数错误继续传递
        }
        static::$assertion = debug_backtrace(false);
        static::$assertion = static::$assertion[1];//从trace栈获取assert参数内容
        extract(func_get_arg(4));//恢复assert上下文环境
        assert(static::$assertion['args'][0]);//运行断言
        /*清除断言数据*/
        static::$assertion = null;
        return true;
    }

    /**
     * Assertion Handler
     * 断言错误回调
     *
     * @method callback
     */
    public static function callback($file, $line, $code, $message = null)
    {
        header('Content-type: text/html; charset=utf-8', true, 500);
        $trace = debug_backtrace(false);
        array_shift($trace);
        array_shift($trace);
        array_pop($trace);

        if ($assertion = &static::$assertion) {
            //php53 assert 双参数回调
            $file = $assertion['file'];
            $line = $assertion['line'];
            $code = $assertion['args'][0];
            if (isset($assertion['args'][1])) {
                $message = $assertion['args'][1];
            }
            array_shift($trace);
        }

        echo "\n断言错误触发：\n<br>",
        '<b><q>', $message, "</q></b><br>\n",
        "触发位置$file 第$line 行:<br>\n 判断逻辑<b><code> $code </code></b>\n<br/>",
        '(这里通常不是错误位置，是错误的调用方式或者参数引起的，请仔细检查)',
        "<br>\n<small>(tips:断言错误是在正常逻辑中不应出现的情况，生产环境关闭系统断言提高性能)</small>\n<br>";

        echo '<br><hr>函数调用栈【call stack】<br><hr><ol>';
        foreach (array_reverse($trace) as $i => $t) {
            echo '<li><pre>';
            if ($arg = $t['args']) {
                $arg = '('.array_reduce($arg, function ($s, &$v) {
                    return $s.print_r($v, true).',';
                });
                $arg[strlen($arg) - 1] = ')';
            } else {
                $arg = '()';
            }
            if (isset($t['class'])) {
                echo "${t['class']}${t['type']}${t['function']}$arg;\n";
            } else {
                echo "[${t['file']}${t['line']}]${t['function']}$arg;\n";
            }
            echo '</pre></li>';
        }
        echo '</ol>';
    }

    public function __destory()
    {
        ob_flush();
    }
}
