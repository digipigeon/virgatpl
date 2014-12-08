<?php

/**
 * RainTPL easy template engine compiles HTML templates to PHP.
 *
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation;
 * either version 3 of the License, or any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 * 
 *  @author Federico Ulfo <rainelemental@gmail.com>
 *  @copyright 2006 - 2011 Federico Ulfo | www.federicoulfo.it
 *  @link http://www.raintpl.com
 *  @version 2.2
 *  @package RainFramework
 */

class VirgaTPL_Core{

	private $var = array( );		// template var
	protected $current_scope = false;

	static 	$tpl_dir = "tpl/",			// template directory
			$cache_dir = "tmp/",	// template cache directory
			$base_url = null;			// template base url (useful for absolute path eg. http://www.raintpl.com )			

	/**
	 * Assign variable
	 * eg. 	$t->assign('name','duck');
	 *
	 * @param mixed $variable_name Name of template variable or associative array name/value
	 * @param mixed $value value assigned to this variable. Not set if variable_name is an associative array
	 */

	public function assign( $variable, $value = null ){
		if( is_array( $variable ) )			
			foreach( $variable as $name => $value ) 
				$this->var[ $name ] = $value;
		elseif( is_object( $variable ) )
			foreach( get_object_vars( $variable ) as $name => $value ) 
				$this->var[ $name ] = $value;
		else
			$this->var[ $variable ] = $value;
	}



	/**
	 * Draw the template
	 * eg. 	$html = $tpl->draw( 'demo', TRUE ); // return template in string
	 * or 	$tpl->draw( $tpl_name ); // echo the template
	 *
	 * @param string $tpl_name  template to load
	 * @param boolean $return_string  true=return a string, false=echo the template
	 * @return string
	 */

	public function draw( $tpl_name, $return_string = false ){

		$tpl_basename 		= basename( $tpl_name );															// template basename
		$tpl_basedir 		= strpos($tpl_name,"/") ? dirname($tpl_name) . '/' : null;							// template basedirectory
		$tpl_dir 			= raintpl::$tpl_dir . $tpl_basedir;													// template complete directory
		$tpl_filename 		= $tpl_dir . $tpl_basename . ".html";											// template complete filename
		$tpl_cache_dir 		= raintpl::$cache_dir . $tpl_dir;												// template cache directory
		$tpl_cache_filename	= $tpl_cache_dir . $tpl_basename . '.php';										// template cache filename

		// if the template doesn't exsist throw an error
		if( !file_exists( $tpl_filename ) ){
			trigger_error( "Template $tpl_basename not found!" );
			$error = '<div style="background:#f8f8ff;border:1px solid #aaaaff;padding:10px;">Template <b>'.$tpl_basename.'</b> not found</div>';
			if( $return_string ) return $error; else{ echo $error; return null; }
		}

		// file doesn't exsist, or the template was updated, Rain will compile the template
		if( !file_exists( $tpl_cache_filename ) || filemtime($tpl_cache_filename) < filemtime($tpl_filename) )
			$this->compileFile( $tpl_basedir, $tpl_filename, $tpl_cache_dir, $tpl_cache_filename );

		// load the template
		ob_start();
		// extract all variables assigned to the template
		include $tpl_cache_filename;
		$raintpl_contents = ob_get_contents();
		ob_end_clean();

		// return or print the template
		if( $return_string ) return $raintpl_contents; else echo $raintpl_contents;
		
	}
	
	public function set_scope(&$scope = NULL){
		if ($scope){
			$scope->parent($this->current_scope);
			$this->current_scope = $scope;			
		}else{
 			$scope = $this->current_scope->parent();
			unset($this->current_scope);
			$this->current_scope = &$scope;			
		}
	}


	/**
	 * Compile and write the compiled template file
	 * @access private
	 */
	protected function compileFile( $tpl_basedir, $tpl_filename, $tpl_cache_dir, $tpl_cache_filename ){

		//delete the cache of the selected template
		if ($old_cache = glob($tpl_cache_filename . '*.php')) array_map("unlink", $old_cache);

		//read template file
		$template_code = file_get_contents($tpl_filename);

		//xml substitution
		$template_code = preg_replace("/\<\?xml(.*?)\?\>/", "##XML\\1XML##", $template_code);

		//disable php tag
		$template_code = preg_replace(array("/\<\?/","/\?\>/"), array("&lt;?","?&gt;"), $template_code);

		//xml re-substitution
		$template_code = preg_replace("/\#\#XML(.*?)XML\#\#/", "<?php echo '<?xml' . stripslashes('\\1') . '?>'; ?>", $template_code);

		//compile template
		$template_compiled = "<?php if(!class_exists('raintpl')){exit;}?>" . $this->compileTemplate($template_code, $tpl_basedir);

		// create directories
		if( !is_dir( $tpl_cache_dir ) )
			mkdir( $tpl_cache_dir, 0755, true );

		//write compiled file
		file_put_contents( $tpl_cache_filename, $template_compiled );			
	}

	/**
	 * Compile template
	 * @access protected
	 */
	public function compileTemplate( $template_code){

		//tag list
		$tag_regexp = '/(\{noparse\})|(\{\/noparse\})|(\{ignore\})|(\{\/ignore\})|(\{include="(?:.*?)"\})/';

		//split the code with the tags regexp
		$template_code = preg_split ( $tag_regexp, $template_code, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		//compile the code
		$compiled_code = $this->compileCode( $template_code);

		//return the compiled code
		return $compiled_code;
	}

	/**
	 * Compile the code
	 * @access private
	 */
	protected function compileCode($parsed_code){
		//variables initialization
		$level				= 0;
		$compiled_code		= '';
		$comment_is_open	= false;
		$ignore_is_open		= false;

	 	//read all parsed code
	 	while( $html = array_shift( $parsed_code ) ){

	 		//close ignore tag
	 		if( !$comment_is_open && preg_match( '/\{\/ignore\}/', $html ) ){
	 			$ignore_is_open = false;
	 		}elseif( $ignore_is_open ){
		 		//code between tag ignore id deleted
	 			//ignore the code
	 		}elseif( preg_match( '/\{\/noparse\}/', $html ) ){
		 		//close no parse tag
	 			$comment_is_open = false;	 			
	 		}elseif( $comment_is_open ){
				//code between tag noparse is not compiled
 				$compiled_code .= $html;
	 		}elseif( preg_match( '/\{ignore\}/', $html ) ){
	 		//ignore
	 			$ignore_is_open = true;
	 		}elseif( preg_match( '/\{noparse\}/', $html ) ){
		 		//noparse
	 			$comment_is_open = true;
	 		}elseif( preg_match( '/(?:\{include="(.*?)"\})/', $html, $code ) ){
			//include tag

				//variables substitution
				$include_var = $this->var_replace( $code[ 1 ], $left_delimiter = null, $right_delimiter = null, $php_left_delimiter = '".' , $php_right_delimiter = '."');

				//dynamic include
				$compiled_code .= '<?php $tpl = new RainTPL();' .
							 '$tpl_dir_temp = raintpl::$tpl_dir;' .
							 '$tpl->assign( $this->var );' .
							 'raintpl::$tpl_dir .= dirname("'.$include_var.'") . ( substr("'.$include_var.'",-1,1) != "/" ? "/" : "" );' .
							 ( !$this_loop_name ? null : '$tpl->assign( "key", $key'.$this_loop_name.' ); $tpl->assign( "value", $value'.$this_loop_name.' );' ).
							 '$tpl->draw( basename("'.$include_var.'"));'.
							 'raintpl::$tpl_dir = $tpl_dir_temp;' . 
							 '?>';
			}else{
				$this->parse_plugin($html);

				//variables substitution (es. {$title})
				$compiled_code .= $this->var_replace( $html, $left_delimiter = '\{', $right_delimiter = '\}', $php_left_delimiter = '<?php ', $php_right_delimiter = ';?>', $echo = true );
			}
		}

		return $compiled_code;
	}
	
	/**
	 * Variable substitution
	 *
	 * @param string $html Html code
	 * @param string $tag_left_delimiter default ''
	 * @param string $tag_right_delimiter default ''
	 * @param string $php_left_delimiter default <?php=
	 * @param string $php_right_delimiter  default ;?>
	 * @param string $loop_name Loop name
	 * @param string $echo if is true make the variable echo
	 * @return string Replaced code
	 */
	public function var_replace($html, $tag_left_delimiter = '', $tag_right_delimiter = '', $php_left_delimiter = null, $php_right_delimiter = null, $echo = null){
		// const
		$html = preg_replace('/\{\#(\w+)\#\}/', $php_left_delimiter . ($echo ? " echo " : null) . '\\1' . $php_right_delimiter, $html );
		
		//all variables
		preg_match_all('/' . $tag_left_delimiter . '\$(\w+(?:\.\${0,1}(?:\w+))*(?:\[\${0,1}(?:\w+)\])*(?:\-\>\${0,1}(?:\w+))*)(.*?)' . $tag_right_delimiter . '/', $html, $matches, PREG_SET_ORDER);

		foreach ($matches as $match_part){
			$function = null;
			$params = null;
			
			//complete tag ex: {$news.title|substr:0,100}
			$tag = $match_part[0];

			//variable name ex: news.title
			$var = $match_part[1];
			
			//function and parameters associate to the variable ex: substr:0,100
			$modifier = $this->var_replace($match_part[2]);
			
			//variable path split array (ex. $news.title o $news[title]) or object (ex. $news->title)
			$temp = preg_split( "/\.|\[|\-\>/", $var );
			
			//variable name
			$var_name = $temp[0];
			
			//variable path
			$variable_path = substr($var, strlen($var_name));
			
			//parentesis transform [ e ] in [" e in "]
			$variable_path = str_replace('[', '["', $variable_path);
			$variable_path = str_replace(']', '"]', $variable_path);
			
			//transform .$variable in ["$variable"]
			$variable_path = preg_replace('/\.\$(\w+)/', '["$\\1"]', $variable_path);
			
			//transform [variable] in ["variable"]
			$variable_path = preg_replace('/\.(\w+)/', '["\\1"]', $variable_path);

			if ($var_name == 'GLOBALS'){
				$php_var = '$GLOBALS' . $variable_path;	
			}elseif($this->current_scope && is_callable($c = $this->current_scope->scope_replace)){
				$php_var = $c($var_name, $variable_path);
			}else{
				$php_var = "\$" . $var_name . $variable_path;
			}
			
			// check if there's an operator = in the variable tags, if there's this is an initialization so it will not output any value
			$is_init_variable = preg_match("/(.*?)\=(.*?)/", $modifier);
			
			$full_var = $php_left_delimiter . ( !$is_init_variable && $echo ? 'echo ' : null ) . $this->modifier($php_var, $modifier) . $php_right_delimiter;
			
			$html = str_replace($tag, $full_var, $html);
		}
		return $html;
	}
	
	protected function modifier($php_var, $modifier){
		foreach($this->plugins as &$plugin){
			foreach($plugin->modifier as $key => $val){
				if (preg_match("/$val/",$modifier,$matches)){
					return $plugin->modify($key, $php_var, $matches);
				}
			}
		};
		//if there's a function
//		if ($modifier && $modifier[0] == '|'){
//			//split function by function_name and parameters (ex substr:0,100)
//			$function_split = explode(':', substr($modifier, 1), 2);
//			
//			//function name
//			$function = $function_split[0];
//			
//			//function parameters
//			$params = (isset($function_split[1])) ? $function_split[1] : null;
//
//			return $params ? "( $function( $php_var, $params ) )" : "$function( $php_var )";
//		} else {
			return $php_var . $modifier;
//		}
	}
}