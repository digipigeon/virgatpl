<?php defined('SYSPATH') or die('No direct script access.');

class View extends Kohana_View {

	public function set_filename($file){
		if (($path = Kohana::find_file('views', $file)) === FALSE){
			if (($path = Kohana::find_file('views/virgatpl', $file,'tpl')) === FALSE){
				throw new Kohana_View_Exception('The requested view :file could not be found', array(
					':file' => $file,
				));
			}
			$path = substr($path,0,-3) . 'php';
			$path = str_replace('/views/virgatpl/', '/views/', $path);
		}

		// Store the file path locally
		$this->_file = $path;

		return $this;
	}

	protected static function capture($kohana_view_filename, array $kohana_view_data){
		$template = substr($kohana_view_filename,0,-3) . 'tpl';
		$template = str_replace('/views/', '/views/virgatpl/', $template);
		if (!empty($kohana_view_filename) && file_exists($template) && (!file_exists($kohana_view_filename) || filectime($template) > filectime($kohana_view_filename))){
			$virga = new VirgaTPL();
			self::create_folder_tree($kohana_view_filename);
			file_put_contents($kohana_view_filename, $virga->compileTemplate(file_get_contents($template)));
		}
		return parent::capture($kohana_view_filename, $kohana_view_data);		
	}
	
	protected static function create_folder_tree($file){
		$path = '';
		
		$folders = explode('/',$file);
		array_shift($folders);
		$path .= '/' . array_shift($folders);
		$path .= '/' . array_shift($folders);
		$path .= '/' . array_shift($folders);
		$path .= '/' . array_shift($folders);
		array_pop($folders);
		foreach($folders as $folder){
			$path .= '/' . $folder;
			if (!is_dir($path)){
				mkdir($path);
			}
		}
	}
}