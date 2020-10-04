<?php

namespace flundr\cache;

class RequestCache
{

	public $cacheIdentifier;
	public $cacheExpire;
	public $cacheDirectory = ROOT . 'cache';

	public function __construct($identifier, $expire = 60) {
		$this->cacheIdentifier = hash('crc32', $identifier);
		$this->cacheExpire = $expire;
	}

	public function get() {

		if ($this->cacheExpire == 0) {return null;}

		$cacheFile = $this->cacheDirectory . DIRECTORY_SEPARATOR . $this->cacheIdentifier;

		if($this->cache_is_valid($cacheFile, $this->cacheExpire)) {
			$serializedData = file_get_contents($cacheFile);
			return unserialize($serializedData);
		}

		return null;

	}

	public function save($data) {

		if ($this->cacheExpire == 0) {return;}

		if (!is_dir($this->cacheDirectory)) {mkdir($this->cacheDirectory);}
		$cacheFile = $this->cacheDirectory . DIRECTORY_SEPARATOR . $this->cacheIdentifier;

		file_put_contents($cacheFile, serialize($data));
	}

	public function flush() {
		$files = glob($this->cacheDirectory . DIRECTORY_SEPARATOR . '*');
		foreach($files as $file){
		  if(is_file($file))
		    unlink($file);
		}
	}

	private function cache_is_valid($filepath, $expireTime) {
		if (file_exists($filepath) && (time() - $expireTime < filemtime($filepath))) {
			return true;
		}
		return false;
	}

}