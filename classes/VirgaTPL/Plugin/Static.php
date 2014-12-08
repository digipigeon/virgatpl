<?php defined('SYSPATH') OR die('No direct access allowed.');

class VirgaTPL_Plugin_Static extends VirgaTPL_Plugin{
	public $tag = '(\{\w+::\w+\((?:.*?)\)\})';
	public $regex = Array(
		'(?:\{(\w+::\w+)(\((?:.*?)\))\})'
	);	
		
	public function parse($match, $html, $code){
		$static = $code[1];
		//parse the parameters
		$parsed_param = isset($code[2]) ? $this->parent->var_replace($code[2]) : '()';

		//if code
		return "<?php echo {$static}{$parsed_param}; ?>";
	}
}