<?php

namespace flundr\file;

use flundr\file\ImageResizer;

class FileUpload {

	/*********************
	**	 File Uploads	**
	*********************/

	//Zugelassene Dateiformate und Größe und Uploadverzeichnis
	private $allowedFiles;
	private $maxFileSize;
	private $internalUploadPath;
	private $publicUploadPath;
	private $maxFiles;
	private $overwrite;

	// Constructor mit Standardwerten
	public function __construct(
		$path, // Default UploadPfad
		array $extensions = ["jpg","jpeg","gif"], // Default erlaubte Extensions
		$size  = (25 * 1024 * 1024), // Default Maximale Dateigröße
		$maxFiles = 10, // Default Anzahl parallel Uploads
		$overwrite = false // Dateien überschreiben
		) {

		// Falls PHP in den erlaubten Extensions steht abbrechen!
		if (preg_grep('/php*/i', $extensions)) {
			die ("Fatal Security Risk: PHP-Upload in UploadClass allowed");
		}

		// Extension Whitelist laden
		$this->allowedFiles = $extensions;

		// Max FileSize höchstens 100mb
		if ($size > 100) {$size=100;}
		$this->maxFileSize = $size * 1024 * 1024;
		$this->internalUploadPath = PUBLICFOLDER.$path;
		$this->publicUploadPath = $path;
		$this->maxFiles = $maxFiles;
		$this->overwrite = $overwrite;

	} // End Constructor


	// Funktion um Datein vorzubereiten und Hochzuladen
	public function upload($files) {

		if (!is_array($files)) {
			return 'Error no Files submitted'; // If files is not an Array return
		}

		$files = $this->niceFilesArray($files); // Rearrange Form Files[]
		return $this->writeToDisk($files); // Save Files to Disk and Return Saved Data
	} // End upload



	// Upload Images via Tiny MCE
	public function uploadTiny($files) {
		if (!is_array($files)) {
			header($_SERVER['SERVER_PROTOCOL'] . ' 400 Tiny Image Files missing');
			die ('No Files provided');
		}
		return $this->writeToDisk($files); // Save Files to Disk and Return Saved Data
	} // End upload




	// Rearrange the strange <form> FileArray
	private function niceFilesArray($formFiles) {

		// never upload more than $this->maxFiles
		$anzahlFiles = count($formFiles['name']);
		if ($anzahlFiles > $this->maxFiles) {$anzahlFiles = $this->maxFiles;}

		// Reformat formFiles into nice Array -> files[]
		for ($i=0; $i < $anzahlFiles; $i++) {
			$files[$i]['name'] = strip_tags($formFiles['name'][$i]);
			$files[$i]['type'] = strip_tags($formFiles['type'][$i]);
			$files[$i]['tmp_name'] = strip_tags($formFiles['tmp_name'][$i]);
			$files[$i]['error'] = strip_tags($formFiles['error'][$i]);
			$files[$i]['size'] = strip_tags($formFiles['size'][$i]);
		} // end For Loop

		unset($formFiles); // Save Memory
		return $files;
	}


	private function createThumbnail($origFile, $maxWidth = 300, $suffix = '_thumb') {

		try {

			$img = new ImageResizer($origFile['path']);

			$img->resizeToWidth($maxWidth);

			$internalpath = $this->internalUploadPath . DIRECTORY_SEPARATOR . $origFile['seedednameonly'] . $suffix . '.' . $origFile['ext'];
			$thumbpath = $this->publicUploadPath . DIRECTORY_SEPARATOR . $origFile['seedednameonly'] . $suffix . '.' . $origFile['ext'];

			$img->save($internalpath);


			return $thumbpath;

		} catch (\Exception $e) {
			return null; // Throws no errors! Mot likely the File just was not an image
		}

	}



	// Daten auf Datenträger ablegen
	private function writeToDisk($fileData) {

		$uploadedFiles = [];
		$errorFiles = [];

		if (!is_dir($this->publicUploadPath)) {mkdir($this->publicUploadPath);}

		foreach ($fileData as $file) {

			// Splitt Filename and Extension
			$file['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);
			$file['nameonly'] = pathinfo($file['name'], PATHINFO_FILENAME);

			// Check if Filesize is ok
			if ($file['size'] > $this->maxFileSize ) {
				$file['error'] = 'Dateigröße überschritten';
				unset($file['tmp_name']); // Dont Expose the tmp_name
				array_push ($errorFiles, $file);
				continue;
			}

			// Check if Extension is is allowed
			elseif (!in_array(strtolower($file['ext']), $this->allowedFiles) ) {
				$file['error'] = 'Dateityp nicht erlaubt';
				unset($file['tmp_name']);// Dont Expose the tmp_name
				array_push ($errorFiles, $file);
				continue;
			}

			else {
				// Seed the Filename and build Path
				$randSeed = uniqid();
				$seededFileName = sprintf('%s_%s.%s', $file['nameonly'],$randSeed,$file['ext']);
				$file['seedednameonly'] = sprintf('%s_%s', $file['nameonly'],$randSeed);
				$file['pathinternal'] = $this->publicUploadPath.DIRECTORY_SEPARATOR.$seededFileName;
				$file['path'] = $this->publicUploadPath.DIRECTORY_SEPARATOR.$seededFileName;



				// Save file on disk
				move_uploaded_file($file['tmp_name'], $file['pathinternal']);

				// create Thumbnails
				if (strToLower($file['ext']) == 'jpg' || strToLower($file['ext']) == 'png') {
					//$file['medium'] = $this->createThumbnail($file,1400,'_medium');
					if ($file['size'] < (5 * 1024 * 1024)) { // only process Images less than 5mb
						$file['thumb'] = $this->createThumbnail($file,500,'_thumb');
					}
				}

				unset($file['error']);
				unset($file['tmp_name']);

				array_push ($uploadedFiles, $file); // Add File to the Uploads Array
			} // End good Upload

		} // End foreach fileData

		$returnArray['uploads'] = $uploadedFiles;
		$returnArray['errors'] = $errorFiles;

		return $returnArray; // Returns Uploads and Errors as Arrays

	} // End writeToDisk


} // End Class fileUploads
?>