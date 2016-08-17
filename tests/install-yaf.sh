#!/usr/bin/env bash

TESTS_PATH=`dirname $(readlink -f $0)`
if [[ ${TRAVIS_PHP_VERSION:0:2} == "5." ]]; then 
    YAF_VERSION=yaf-2.3.5
else 
    YAF_VERSION=yaf-3.0.3
fi

curl https://pecl.php.net/get/${YAF_VERSION}.tgz | tar zx -C ./
cd ${YAF_VERSION}; phpize;

./configure && make && make install

phpenv config-add $TESTS_PATH/ini/yaf.$environ.ini