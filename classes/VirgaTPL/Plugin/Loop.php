<?php defined('SYSPATH') OR die('No direct access allowed.');

class VirgaTPL_Plugin_Loop extends VirgaTPL_Plugin{
	public $level;
	public $tag = '(\{(?:loop(?:\s+)name="(?:.*?)")\})|(\{(?:\/loop)\})';
	public $regex = Array(
		'loop'			=> '(?:\{loop(?:\s+)name="(.*?)"\})',
		'close_loop'	=> '\{\/loop\}'
	);	
			
	public function parse($match, $html, $code){
		switch($match){
			case 'loop':	 			
				//replace the variable in the loop
				$var = $this->parent->var_replace( '$' . $code[1]);
	 			$scope = new VirgaTPL_Scope();
				$scope->scope_replace = function($var_name, $variable_path){
					switch($var_name){
						case 'key':
							return '$key' . VirgaTPL_Scope::$level;
						case 'value':
							return '$value' . VirgaTPL_Scope::$level . $variable_path;
						case 'counter':
							return '$counter' . VirgaTPL_Scope::$level;
						default:
							return "\$" . $var_name . $variable_path;
					}					
				};
				$this->parent->set_scope($scope);

				//loop variables
				$counter = "\$counter" . VirgaTPL_Scope::$level;	// count iteration
				$key = "\$key" . VirgaTPL_Scope::$level;			// key
				$value = "\$value" . VirgaTPL_Scope::$level;		// value
				
				//loop code
				return "<?php $counter = 0; if( is_array( $var ) ) foreach( $var as $key => $value ){ ?>";
			case 'close_loop':
				
				//close loop tag
				//iterator
				$counter = "\$counter" . VirgaTPL_Scope::$level;
				$this->parent->set_scope($scope);

				//decrease the loop counter
				$this->level--;

				//close loop code
				return "<?php $counter++; } ?>";				
		}

		//function
		$function = $code[1];

		//parse the parameters
		$parsed_param = isset($code[2]) ? $this->parent->var_replace($code[2]) : '()';

		//if code
		return "<?php echo {$function}{$parsed_param}; ?>";
	}
}