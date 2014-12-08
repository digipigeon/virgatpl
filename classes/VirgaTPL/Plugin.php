<?php defined('SYSPATH') OR die('No direct access allowed.');

abstract class VirgaTPL_Plugin{
	protected $parent;
	public $regex = Array();
	public $tag = Array();
	public $modifier = Array();
	
	abstract public function parse($match, $html, $code);
	
	public function  __construct(&$parent){
		$this->parent = &$parent;
	}
}