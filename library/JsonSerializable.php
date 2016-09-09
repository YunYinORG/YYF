<?php
/**
* PHP5.3 json 接口兼容
* 仅保证代码运行
* Just do NOTHING
*/
interface JsonSerializable{
    public function jsonSerialize();
}