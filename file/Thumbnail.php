<?php

namespace flundr\file;

use Intervention\Image\ImageManager;

class Thumbnail {

	public $width = 480;
	public $height = 360;
	public $quality = 75;
	public $format = 'jpg';
	public $subfolder = null;
	public $suffix = '_thumb';

	private $converter;
	private $convertableFileExtensions = ['jpg','jpeg','png','gif'];
	private $outputPath;
	private $outputURL;

	public function __construct() {
		$this->converter = new ImageManager();
	}

	public function create($pathToFile) {

		$pathInfo = pathinfo($pathToFile);
		if (!$this->is_convertable($pathInfo['extension'])) {return null;}

		$this->set_output_paths($pathInfo);

		try {

			$img = $this->converter->make($pathToFile);
			$img->fit($this->width, $this->height, function ($constraint) { $constraint->upsize(); });
			$img->save($this->outputPath, $this->quality, $this->format);

			return $this->outputURL;

		} catch (\Exception $e) {
			return null;
		}

	}


	private function is_convertable($extension) {
		if (in_array($extension, $this->convertableFileExtensions)) {return true;}
		return false;
	}


	private function set_output_paths($pathInfo) {

		// Prevent Thumbnails to overwrite original Files
		if (is_null($this->subfolder) && is_null($this->suffix)) {
			$this->suffix = '_thumb';
		}

		$filename = $pathInfo['filename'] . $this->suffix . '.' . $this->format;
		$path = $pathInfo['dirname'] . DIRECTORY_SEPARATOR;

		if ($this->subfolder) {
			$path = $path . $this->subfolder . DIRECTORY_SEPARATOR;
			$this->outputURL = '/' . $this->subfolder;
		}

		if (!is_dir($path)) {mkdir($path);}

		$filename = $this->sanitize($filename);

		$this->outputPath = $path . $filename;
		$this->outputURL .= '/' . $filename;

	}

	private function sanitize($filename) {
		$filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename); // Whitelist for filename Characters
		$filename = mb_ereg_replace("([\.]{2,})", '', $filename); // Remove multiple Dots
		return $filename;
	}

}