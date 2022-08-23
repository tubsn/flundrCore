<?php

namespace flundr\file;

use flundr\file\Thumbnail;
use flundr\auth\Auth;

class Storage {

	public $folder = 'uploads';			// Subfolder in the PUBLIC Folder
	public $uploadContainerName = 'uploads';	// HTML Form Input Container Name
	public $formats = ['jpg','jpeg','gif','png','webp'];
	public $maxSize = 25 * 1024 * 1024; // 25mb
	public $maxFiles = 10;
	public $seedFilenames = true;
	public $forceFilename = false;	

	public $thumbnails = false;
	public $thumbWidth = 480;
	public $thumbHeight = 360;
	public $thumbQuality = 75;
	public $thumbFolder = null;
	public $thumbSuffix = null;

	private $failedFiles = [];
	private $storedFiles = [];

	// Constructor mit Standardwerten
	public function __construct($folder = null) {
		if ($folder) {$this->folder = $folder;}
		$this->check_for_restricted($this->formats);
	}

	public function store($fileContainer) {

		//dd($fileContainer);

		$files = $this->validate_upload($fileContainer);
		$this->upload_files($files);

		if ($this->thumbnails) {
			$this->create_thumbnails();
		}

		return $this->storedFiles;
	}

	public function put($fileContainer) {
		return $this->store($fileContainer);
	}

	public function stored() { return $this->storedFiles; }
	public function uploaded() {return $this->stored();}

	public function failed() { return $this->failedFiles; }
	public function failed_files() {return $this->failed();}

	public function create_thumbnails() {

		$thumb = new Thumbnail();
		$thumb->width = $this->thumbWidth;
		$thumb->height = $this->thumbHeight;
		$thumb->quality = $this->thumbQuality;
		$thumb->suffix = $this->thumbSuffix;
		$thumb->subfolder = $this->thumbFolder;

		foreach ($this->storedFiles as $key => $file) {

			$thumbnailURL = $thumb->create($this->absolute_disk_path($file));

			if ($thumbnailURL) {
				$thumbnailURL = '/' . $this->folder . $thumbnailURL;
				$this->storedFiles[$key]['thumbnail'] = $thumbnailURL;
			}

		}

	}

	private function storage_path() {
		return PUBLICFOLDER . $this->folder . DIRECTORY_SEPARATOR;
	}

	private function absolute_disk_path($fileInfos) {
		$path = $this->storage_path() . $fileInfos['seeded_filename'] ?? $fileInfos['filename'];
		return $path . '.' . $fileInfos['extension'];
	}


	private function upload_files($files) {

		foreach ($files as $file) {

			$file['filename'] = $this->get_filename($file['name']);
			$file['extension'] = $this->get_extension($file['name']);
			$file['created_by'] = Auth::get('id');

			if ($this->file_too_big($file)) {continue;}
			if ($this->extension_not_allowed($file)) {continue;}

			$this->move_to_disk($file);
		}

	}

	private function move_to_disk($file) {

		if (!is_dir($this->storage_path())) {mkdir($this->storage_path());}

		$filename = $file['filename'];
		if ($this->seedFilenames) {
			$filename = $this->add_seed($file['filename']);
			$file['seeded_filename'] = $filename;
		}

		$filename = $filename . '.' . $file['extension'];
		if ($this->forceFilename) {
			$filename = $this->forceFilename;
			$file['filename'] = $this->forceFilename;
		}

		$file['url'] = '/' . $this->folder . '/' . $filename;

		$storagePath = $this->storage_path() . $filename;
		$writeOk = move_uploaded_file($file['tmp_name'], $storagePath);

		if ($writeOk) {
			$this->push_to_stored($file);
			return true;
		}

		$this->push_to_failed($file, 'Disk Error - File could not be saved (Harddrive Full?)');
		return false;

	}

	private function validate_upload($fileContainer) {

		if (!is_array($fileContainer)) {
			throw new \Exception('No Files Array submitted', 400);
		}

		if (!isset($fileContainer[$this->uploadContainerName])) {
			throw new \Exception('HTML-Input-Element Field "name" is expected to be "' . $this->uploadContainerName . '[]"', 400);
		}

		// Switch into the Form File Container
		$files = $fileContainer[$this->uploadContainerName];
		$files = $this->convert_html_form_input($files);
		$files = $this->check_number_of_files($files);

		return $files;

	}

	private function get_filename($fullName) {
		return pathinfo($fullName, PATHINFO_FILENAME);
	}

	private function get_extension($fullName) {
		return pathinfo($fullName, PATHINFO_EXTENSION);
	}

	private function file_too_big($file) {
		if ($file['size'] < $this->maxSize ) {return false;}
		$this->push_to_failed($file, 'Filesize too large');
		return true;
	}

	private function extension_not_allowed($file) {
		if (in_array(strtolower($file['extension']), $this->formats) ) {return false;}
		$this->push_to_failed($file, 'Filetype not allowed');
		return true;
	}

	private function add_seed($filename) {
		return $filename . '_' . uniqid();
	}

	private function check_for_restricted($formats) {
		if (preg_grep('/php*/i', $formats)) {
			die ('Fatal Security Risk: *.php extension in Upload Whitelist not allowed');
		}
	}

	private function check_number_of_files($files) {

		if (count($files) > $this->maxFiles) {
			$passedFiles = array_slice($files, 0, $this->maxFiles);
			$ignoredFiles = array_slice($files, $this->maxFiles);

			foreach ($ignoredFiles as $file) {
				$this->push_to_failed($file, 'File ignored - Maximum number of files ('. $this->maxFiles .') per upload reached');
			}

			return $passedFiles;
		}

		return $files;
	}

	private function push_to_failed($file, $reason = 'unspecified Error') {
		$file['error'] = $reason;
		unset($file['tmp_name']);
		unset($file['created_by']);
		array_push ($this->failedFiles, $file);
	}

	private function push_to_stored($file) {
		unset($file['error']);
		unset($file['tmp_name']);
		array_push ($this->storedFiles, $file);
	}

	private function convert_html_form_input($files) {

		$counter = 0;
		$output = [];

		// Converts HTML Form array into a readable one
		foreach ($files as $fieldName => $container) {

			foreach ($container as $value) {

				if ($fieldName == 'full_path') {continue;} // Ignores the PHP8.1 Feature for directories
				if ($fieldName == 'name') {
					$value = $this->sanitize($value);
				}

				$output[$counter][$fieldName] = strip_tags($value);
				$counter++;
			}
			$counter = 0;
		}

		return $output;

	}

	private function sanitize($filename) {
		$filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename); // Whitelist for filename Characters
		$filename = mb_ereg_replace("([\.]{2,})", '', $filename); // Remove multiple Dots
		return $filename;
	}

	public function install($database, $forceNewTableName = null) {

		$tableName = $database->table;
		if ($forceNewTableName) {$tableName = $forceNewTableName;}

		$tableName = htmlspecialchars($tableName);
		$tableName = preg_replace( '/[\W]/', '', $tableName);

		$database->query('
			CREATE TABLE `'.$tableName.'` (
			 `id` int(10) NOT NULL AUTO_INCREMENT,
			 `created` timestamp NOT NULL DEFAULT current_timestamp(),
			 `created_by` varchar(60) DEFAULT NULL,
			 `name` varchar(255) DEFAULT NULL,
			 `type` varchar(120) DEFAULT NULL,
			 `size` int(10) DEFAULT NULL,
			 `filename` varchar(255) DEFAULT NULL,
			 `seeded_filename` varchar(255) DEFAULT NULL,
			 `extension` varchar(20) DEFAULT NULL,
			 `thumbnail` varchar(255) DEFAULT NULL,
			 `url` varchar(255) DEFAULT NULL,
			 PRIMARY KEY (`ID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
		');

	}

}
