<?php defined('SYSPATH') OR die('No direct access allowed.');

class VirgaTPL_Plugin_Function extends VirgaTPL_Plugin{
	public $tag = '(\{function="(?:.*?)"\})';
	public $regex = Array(
		'(?:\{function="(.*?)(\((.*?)\)){0,1}"\})'
	);	
	public $modifier = Array(
		'^\|(.*)'
	);
		
	public function parse($match, $html, $code){
		//function
		$function = $code[1];

		//parse the parameters
		$parsed_param = isset($code[2]) ? $this->parent->var_replace($code[2]) : '()';

		//if code
		return "<?php echo {$function}{$parsed_param}; ?>";
	}
	
	public function modify($key, $php_var, $matches){
		//split function by function_name and parameters (ex substr:0,100)
		$function_split = explode(':', $matches[1], 2);
		
		//function name
		$function = $function_split[0];
		
		//function parameters
		$params = (isset($function_split[1])) ? $function_split[1] : null;

		return $params ? "( $function( $php_var, $params ) )" : "$function( $php_var )";
		
	}
}