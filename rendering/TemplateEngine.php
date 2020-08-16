<?php

namespace flundr\rendering;

class TemplateEngine {

	private $templateBlocks;
	private $templateData;
	private $templateDirectory = TEMPLATES;
	private $templateExtension = TEMPLATE_EXTENSION;

	public function __construct(array $templateBlocks, array $templateData = null) {
		$this->templateBlocks = $templateBlocks;
		$this->templateData = $templateData;
	}

	public function render() {

		// Extract Template Variables into the current Scope
		extract($this->templateData, EXTR_SKIP);

		foreach ($this->templateBlocks as $name => $path) {

			if (!$path) {continue;}

			// Fixes Forward and Backward Slash issues in Paths
			$path = str_replace("/", DIRECTORY_SEPARATOR, $path);

			$templatePath = TEMPLATES . $path . TEMPLATE_EXTENSION;
			if (!file_exists($templatePath)) {
				echo "\n<pre><mark>Warning: ". ucwords($name) . "-Template not found: ". $templatePath. "</mark></pre>\n";
				continue;
			}
			require $templatePath;
		}

	}

	public function tokens() {
		return $this->templateData;
	}

}

