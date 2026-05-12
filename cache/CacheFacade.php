<?php

namespace flundr\cache;

class CacheFacade {
	
	public static function bind($identifier, $content, int $expire = 30) {
		$cacheKey = self::buildIdentifier($identifier);
		$cache = new RequestCache($cacheKey, $expire);
		$cache->save($content);
	}

	public static function save($identifier, $content, int $expire = 30) {
		self::bind($identifier, $content, $expire);
	}

	public static function get($identifier) {
		$cacheKey = self::buildIdentifier($identifier);
		$cache = new RequestCache($cacheKey);
		return $cache->get();
	}

	public static function expire($identifier) {
		$cacheKey = self::buildIdentifier($identifier);
		$cache = new RequestCache($cacheKey);
		return $cache->cacheExpire;
	}

	private static function buildIdentifier($identifier) {
		if (is_array($identifier)) {
			return json_encode($identifier);
		}

		return (string) $identifier;
	}
}