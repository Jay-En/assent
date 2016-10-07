<?php


Class assent{
	private static $result;
	private static $path;
	private static $initialcode;
	private static $supported_languages;
	private static $dir;
	private static $template;
	private static $result_key;



	public static function init($dir, array $supported_languages = [], $startcode = 1000, $primary_language = "EN"){

		if ($primary_language == ""){
			$primary_language = "EN";
		}

		if (!$startcode){
			$startcode = 1000;
		}

		self::$dir = $dir;
		self::$path = $dir."/".$primary_language.".json";
		self::$initialcode = $startcode;


		if(!array_search($primary_language, $supported_languages)){
			$supported_languages[] = $primary_language;
		}

		self::$supported_languages = $supported_languages;


		if(file_exists(self::$path)){
			self::$result = json_decode(file_get_contents(self::$path), true);
		}else{
			self::$result = ['codes' => []];
		}

		self::$template = [];
	}


	public static function setResultTemplate(array $template, $result_key){
		self::$template = $template;
		self::$result_key = $result_key;


		foreach (self::$supported_languages as $language) {
			
			$lang_path = self::$dir."/".$language.".json";


			if(file_exists($lang_path)){
				$json_file = json_decode(file_get_contents($lang_path), true);
			}else{
				$json_file = ['codes' => []];
			}
			if(!isset($json_file['template'])){

				$json_file['template'] = $template;

				$myfile = fopen($lang_path, "w");
				$txt = json_encode($json_file, JSON_PRETTY_PRINT);

				fwrite($myfile, $txt);
				fclose($myfile);
			}
		}
	}
	

	public static function translate($response, $language){
		if(!$language){
			$language = self::$primary_language;
		}

		$lang_path = self::$dir."/".$language.".json";	

		if(file_exists($lang_path)){
			$result = json_decode(file_get_contents($lang_path), true);
		}else{
			$result = ['codes' => []];
		}

		$response['message'] =  isset($result['codes'][$response['code']]) ? $result['codes'][$response['code']] : $response['message'];

		return $response;
	}
	
	public static function set($message){

		$response = [];



		if(is_array($message))
		{
			foreach ($message as $key => $value) {
				$res['code'] =   self::getOrCreate($value);
				$res['message'] =  $value;

				$response[] = $res;
			}
		}else{
				$response['code'] =  self::getOrCreate($message);
				$response['message'] =  $message;
				$response = $response;
		}
		return self::wrap($response);
	
	}


	public static function wrap($response, $language = null)
	{

		if(!$language){
			$lang_path = self::$path;
		}else{

			$lang_path = self::$dir."/".$language.".json";
		}

		if(file_exists($lang_path)){
			$result = json_decode(file_get_contents($lang_path), true);

			if(isset($result['template'])){
				
				 $array = self::putinkey(self::$result_key, $result['template'], $response);
				 array_key_exists(self::$result_key, $result['template']);
				 if(json_encode($array) != json_encode($result['template'])){
				 	$response = $array;
				 }
			}
		}
		return $response;

	}


	private static function putinkey($search_key, array $array, $data){
		if(array_key_exists($search_key, $array)){
			$array[$search_key] =  $data;

		}else{
			foreach ($array as $key => $value) {
				if(is_array($value)){
					$array[$key] = self::putinkey($search_key, $value, $data);
				}
			}
		}
		return $array;
	}

	public static function get($response, $language)
	{
		$result = [];
		if(self::$result_key){
			$result = self::getresultvalue(self::$result_key, $response);
		}else{
			$result = $response;
		}

		if(isset($result['code'])){
			$result = self::translate($result, $language);
		}

		return self::wrap($result,$language);

	}

	private static function getresultvalue($search_key, array $array){
		if(array_key_exists($search_key, $array)){
			return $array[$search_key];

		}else{
			foreach ($array as $key => $value) {
				if(is_array($value)){
					return self::putinkey($search_key, $value);
				}
			}
		}
	}

	private static function getOrCreate($message){
		$code =  array_search($message, self::$result['codes']);

		if($code){


			foreach (self::$supported_languages as $language) {
				
				$lang_path = self::$dir."/".$language.".json";
				if(file_exists($lang_path)){
					$result = json_decode(file_get_contents($lang_path), true);
				}else{
					$result = ['codes' => []];
				}

				if(!isset($result['codes'][$code])){

					$result['codes'][$code] = $message;
					$myfile = fopen($lang_path, "w");
					$txt = json_encode($result, JSON_PRETTY_PRINT);
					fwrite($myfile, $txt);
					fclose($myfile);
				}
			}


			return $code;

		}else{
			end(self::$result['codes']);         		// move the internal pointer to the end of the array
			$key = (int) key(self::$result['codes']);  	// fetches the key of the element pointed to by the internal pointer

			if($key == 0){
				$code = self::$initialcode;
			}else{
				$code = $key+1;
			}


			foreach (self::$supported_languages as $language) {
				
				$lang_path = self::$dir."/".$language.".json";
				if(file_exists($lang_path)){
					$result = json_decode(file_get_contents($lang_path), true);
				}else{
					$result = ['codes' => []];
				}
				$result['codes'][$code] = $message;

				$myfile = fopen($lang_path, "w");
				$txt = json_encode($result, JSON_PRETTY_PRINT);
				fwrite($myfile, $txt);
				fclose($myfile);
			}
            
			return $code;
		}
	}
    

}
