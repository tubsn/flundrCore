<?php

namespace flundr\core;

abstract class View {

	public $data;

	public function render($templateName, array $templateData = []) {
		$this->process_templates([$templateName]);
	}

	protected function process_templates(array $templates) {

		// converts Viewdata to useable $variables in the Template
		if (is_array($this->data)) {
			extract($this->data, EXTR_OVERWRITE);
		}

		foreach ($templates as $template) {

			if (!$template) {continue;}

			$path = TEMPLATES . $template . TEMPLATE_EXTENSION;

			if (!file_exists($path)) {
				echo "\n".'<pre><mark>Warning: Template ' . $template . ' not found</mark></pre>'."\n";
				continue;
			}

			require $path;

		}

	}

}
