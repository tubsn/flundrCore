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

		// Make template Variables available as $tokens
		$tokens = $this->tokens();

		foreach ($this->templateBlocks as $currentTemplateName => $templatePath) {

			if (!$templatePath) {continue;}

			// Fixes Forward and Backward Slash issues in Paths
			$templatePath = str_replace("/", DIRECTORY_SEPARATOR, $templatePath);

			$fullTemplatePath = TEMPLATES . $templatePath . TEMPLATE_EXTENSION;
			if (!file_exists($fullTemplatePath)) {
				echo "\n<pre><mark>Warning: ". ucwords($currentTemplateName) . "-Template not found: ". $fullTemplatePath. "</mark></pre>\n";
				continue;
			}

			require $fullTemplatePath;
		}

	}

	public function tokens() {
		return $this->templateData;
	}

}

