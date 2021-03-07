<?php

namespace flundr\rendering;

class TemplateEngine {

	private $templateBlocks;
	private $templateData;
	private $templateDirectory = TEMPLATES;
	private $templateExtension = TEMPLATE_EXTENSION;

	public function __construct($templateBlocks, array $templateData = null) {

		// Force Templateblocks into Array if it's only one Template
		if (!is_array($templateBlocks)) {
			$templateBlocks = ['main' => $templateBlocks];
		}

		$this->templateBlocks = $templateBlocks;
		$this->templateData = $templateData;
	}

	public function render() {
		echo $this->bake_templates();
	}

	public function burn() {
		return $this->bake_templates();
	}

	public function tokens() {
		return $this->templateData;
	}

	private function bake_templates() {

		// Extract Template Variables into the current Scope
		if ($this->templateData) { extract($this->templateData, EXTR_SKIP); }

		// Make template Variables available as $tokens
		$tokens = $this->tokens();

		ob_start();

		foreach ($this->templateBlocks as $currentTemplateName => $templatePath) {

			if (!$templatePath) {continue;}
			$templatePath = $this->full_path($templatePath);

			if (!file_exists($templatePath)) {
				echo "\n<pre><mark>Warning: ". ucwords($currentTemplateName) . "-Template not found: ". $templatePath. "</mark></pre>\n";
				continue;
			}

			require $templatePath;

		}

		$burnedData = ob_get_contents();

		ob_end_clean();

		return $burnedData;

	}

	private function full_path($path) {
		$path = str_replace("/", DIRECTORY_SEPARATOR, $path);
		return TEMPLATES . $path . TEMPLATE_EXTENSION;
	}

}
