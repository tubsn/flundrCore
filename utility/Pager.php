<?php

namespace flundr\utility;

class Pager
{

	public $htmldata = null;
	public $offset = 0;

	function __construct($numberOfItems, $itemsPerPage = 20){

		// If there is more Items per Page than Items overall
		if ($itemsPerPage >= $numberOfItems) {return false;}

		// Number of Pages
		$numberOfPages = ceil($numberOfItems / $itemsPerPage);

		// Aktuelle Seite per Get "p=x" ziehen max Wert = Anzahl pages
		$currentPage = min($numberOfPages, filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT, [
			'options' => [
				'default'   => 1,
				'min_range' => 1,
			],
		]));

		// Get Parameter rückwärts einlesen so das p hinten hängt
		$getParams = $_GET;

		if ($currentPage == 0) {
			$currentPage =1;
		}

		$pages = range(1, $numberOfPages);

		if ($numberOfPages > 8) {
			$start = array_slice($pages, 0,6);
			$end = array_slice($pages, -2);
			$pages = array_merge($start,['...'],$end);

			if (($currentPage > 5) && $currentPage) {
				$pre = range($currentPage-2, $currentPage+2);			
				$end = array_slice($pages, -1);
				$pages = array_merge([1],['...'],$pre, ['...'], $end);
			}

			if ($currentPage > $numberOfPages-5) {
				$dif = $numberOfPages - $currentPage;
				$pre = range($currentPage-5+$dif, $numberOfPages);			
				$end = array_slice($pages, -2);
				$pages = array_merge([1,2],['...'],$pre);
			}

		}

		$centerLinks ='';
		foreach ($pages as $page) {
			$getParams['p'] = $page;

			if ($page == '...') {$centerLinks .= '<li>&hellip;</li>'; continue;}

			if ($page == $currentPage) {
			$centerLinks .= '<li><a class="active" href="?'.http_build_query($getParams).'">'.$page.'</a></li>';		
			continue;
			}

			$centerLinks .= '<li><a href="?'.http_build_query($getParams).'">'.$page.'</a></li>';
		}

		$prevlink = '';
		if ($currentPage>1) {
			$getParams['p'] = $currentPage-1;
			$prevlink = '<li><a href="?'.http_build_query($getParams).'">«</a></li>';
		}

		$nextlink = '';
		if ($currentPage<$numberOfPages) {
			$getParams['p'] = $currentPage+1;
			$nextlink = '<li><a href="?'.http_build_query($getParams).'">»</a></li>';
		}

		// Pager Zusammensetzen
		//$this->htmldata = '<ul class="pager">'.$centerLinks.'</ul>';
		
		// Pager mit nächste und vorherige Seite
		$this->htmldata = '<ul class="pager">'.$prevlink.$centerLinks.$nextlink.'</ul>';

		// Startitem Offset für das Query als Return-Wert
		$this->offset = 0;
		if ($currentPage>1) {$this->offset = ($currentPage-1) * $itemsPerPage;}

		return $this->htmldata;

	} // End createPager()

}
