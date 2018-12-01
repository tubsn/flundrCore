<?php

namespace flundr\Core;

class Pager
{

	public $htmldata = null;
	public $offset = 0;

	function __construct($numberOfItems, $itemsPerPage = 20){

		// If there is more Items per Page than Items overall
		if ($itemsPerPage >= $numberOfItems) {
			return false;
		}

		// Number of Pages
		$pages = ceil($numberOfItems / $itemsPerPage);

		// Aktuelle Seite per Get "p=x" ziehen max Wert = Anzahl pages
		$currentPage = min($pages, filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT, array(
			'options' => array(
				'default'   => 1,
				'min_range' => 1,
			),
		)));

		// Get Parameter rückwärts einlesen so das p hinten hängt
		$getParams = $_GET;

		// Seite Zurück Link generieren
		if ($currentPage > 1) {
				// p neusetzen mit Aktueller Page -1
			$getParams['p'] = $currentPage - 1;
			$prevlink = '<li><a href="?'.http_build_query($getParams).'">&laquo; vorherige Seite</a></li>';
		} else {
			// Kein Link da erste Seite
			$prevlink = '<li><a class="disabled">&laquo; vorherige Seite</a></li>';
		} // End Currentpage > 1

		// Nächste Seite Link generieren
		if ($currentPage < $pages) {
			// p neusetzen mit Aktueller Page -1
			$getParams['p'] = $currentPage + 1;
			$nextlink = '<li><a href="?'.http_build_query($getParams).'">nächste Seite &raquo;</a></li>';
		} else {
			// Kein Link da erste Seite
			$nextlink = '<li><a class="disabled">nächste Seite &raquo;</a></li>';
		} // End $currentPage < $pages

		// 1,2,3,4,...
		$centerLinks ='';
		for ($i = 1; $i <= $pages; $i++) {
			$getParams['p'] = $i;
			if ($i != $currentPage) {
				$centerLinks .= '<li><a href="?'.http_build_query($getParams).'">'.$i.'</a></li>'; // Normale Seitenlinks
			} else {
				$centerLinks .= '<li><a class="active" href="?'.http_build_query($getParams).'">'.$i.'</a></li>'; // die aktuell gwählte Seite
			}
		}

		// Pager Zusammensetzen
		$this->htmldata = '<ul class="pager">'.$centerLinks.'</ul>';
		// Pager mit nächste und vorherige Seite
		// $this->htmldata = '<ul class="pager">'.$prevlink.$centerLinks.$nextlink.'</ul>';

		// Startitem Offset für das Query als Return-Wert
		$this->offset = ($currentPage - 1) * $itemsPerPage;

		return $this->htmldata; // Alle Pager Daten zurückgeben

	} // End createPager()


}