<?php defined('SYSPATH') OR die('No direct access allowed.');

class VirgaTPL_Scope{
	protected $parent_scope;
	protected $scope_owner;
	public $scope_replace;

	public static $level=0;
	
	public function __construct(){
		self::$level++;
	}
	
	public function parent($scope = NULL){
		if (!$scope) return $this->parent_scope;
		$this->parent_scope = $scope;
	}

	public function my_var($var){
		if (array_key_exists($var, $this->scope_replace)) return $this->scope_replace[$var];
	}
		
	public function __destruct(){
		self::$level--;
	}
}