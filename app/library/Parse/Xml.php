<?php
namespace Parse;
class Xml
{

	/**
	 * XML编码
	 * @param mixed $data 数据
	 * @param string $item 数字索引的子节点名
	 * @return string
	 */
	public static function encode($data, $item = 'item')
	{
		$xml = '<?xml version="1.0" encoding="utf-8"?>';
		$xml .= '<yunyin>';
		$xml .= self::data_to_xml($data);
		$xml .= '</yunyin>';
		return $xml;
	}

/**
 * 数据XML编码
 * @param mixed  $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id   数字索引key转换为的属性名
 * @return string
 */
	private static function data_to_xml($data, $item = 'item', $id = 'id')
	{
		$xml = $attr = '';
		foreach ($data as $key => $val)
		{
			if (is_numeric($key))
			{
				$id && $attr = " {$id}=\"{$key}\"";
				$key         = $item;
			}
			$xml .= "<{$key}{$attr}>";
			$xml .= (is_array($val) || is_object($val)) ? self::data_to_xml($val, $item, $id) : $val;
			$xml .= "</{$key}>";
		}
		return $xml;
	}
}