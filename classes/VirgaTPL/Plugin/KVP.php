<?php defined('SYSPATH') OR die('No direct access allowed.');

class VirgaTPL_Plugin_KVP extends VirgaTPL_Plugin{
	public $tag = '(\{KVP:.*?\})';
	public $regex = Array(
		'(?:\{KVP:(.*?)\})'
	);	
		
	public function parse($match, $html, $code){
		$var = $code[1];
		return "<?php echo KVP::get('$var'); ?>";
	}
}