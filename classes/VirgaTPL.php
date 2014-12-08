<?php defined('SYSPATH') OR die('No direct access allowed.');

class VirgaTPL extends VirgaTPL_Core{
	protected $tag_regexp = '';
	
	protected $plugins = Array();

	public function __construct(){
		$this->plugins['function'] = new VirgaTPL_Plugin_Function($this);
		$this->plugins['condition'] = new VirgaTPL_Plugin_Condition($this);
		$this->plugins['loop'] = new VirgaTPL_Plugin_Loop($this);
		$this->plugins['static'] = new VirgaTPL_Plugin_Static($this);
		$this->plugins['kvp'] = new VirgaTPL_Plugin_KVP($this);
		
		foreach($this->plugins as $plugin){
			$this->tag_regexp .= '|' . $plugin->tag;
		}
	}

	public function compileTemplate( $template_code){
		//tag list
		$tag_regexp = '/(\{(?:loop(?:\s+)name="(?:.*?)")\})|(\{(?:\/loop)\})|(\{function="(?:.*?)"\})|(\{noparse\})|(\{\/noparse\})|(\{ignore\})|(\{\/ignore\})|(\{include="(?:.*?)"\})' . $this->tag_regexp . '/';

		//split the code with the tags regexp
		$template_code = preg_split ( $tag_regexp, $template_code, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		//compile the code
		$compiled_code = $this->compileCode( $template_code);

		//return the compiled code
		return $compiled_code;
	}
	
	protected function parse_plugin(&$compiled_code){
		foreach($this->plugins as $plugin){
			foreach($plugin->regex as $match => $regex){
				if (preg_match("/$regex/", $compiled_code, $code)){
					$compiled_code = $plugin->parse($match, $compiled_code, $code);
				}				
			}
		}
	}
	
	public static function quick_compile($html){
		$virga = new VirgaTPL();
		return $virga->compileTemplate($html);
	}
}