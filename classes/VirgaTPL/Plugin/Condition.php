<?php defined('SYSPATH') OR die('No direct access allowed.');

class VirgaTPL_Plugin_Condition extends VirgaTPL_Plugin{
	public $tag = '(\{(?:if(?:\s+)condition="(?:.*?)")\})|(\{(?:elseif(?:\s+)condition="(?:.*?)")\})|(\{(?:else)\})|(\{(?:\/if)\})';
	public $regex = Array(
		'if'		=> '(?:\{if(?:\s+)condition="(.*?)"\})',
		'else'		=> '\{else\}',
		'elseif'	=> '(?:\{elseif(?:\s+)condition="(.*?)"\})',
		'endif'		=> '\{\/if\}',
	);	
		
	public function parse($match, $html, $code){	
		switch($match){
			case 'if':
				//condition attribute
				$condition = $code[ 1 ];
		
				//variable substitution into condition (no delimiter into the condition)
				$parsed_condition = $this->parent->var_replace($condition);
		
				//if code
				return "<?php if( $parsed_condition ){ ?>";
			case 'else':
				return '<?php }else{ ?>';
			case 'elseif':
				//condition attribute
				$condition = $code[ 1 ];
				
				//variable substitution into condition (no delimiter into the condition)
				$parsed_condition = $this->parent->var_replace( $condition, $tag_left_delimiter = null, $tag_right_delimiter = null, $php_left_delimiter = null, $php_right_delimiter = null);

				//elseif code
				return "<?php }elseif( $parsed_condition ){ ?>";
			case 'endif':
				return '<?php } ?>';
		}
	}
	
}