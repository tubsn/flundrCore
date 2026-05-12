<?php

namespace flundr\cache;

class RequestCache {

	public $cacheIdentifier;
	public $cacheExpire;
	private $cacheDirectory = ROOT . 'cache';
	private $baseCacheDirectory = ROOT . 'cache';

	public function __construct($identifier, int $expire = 60) {
		$this->cacheIdentifier = hash('crc32', $this->build_identifier($identifier));
		$this->cacheExpire = $expire;
	}

	public function cachedir(string $directory): void {
		$normalizedDirectory = rtrim($directory, DIRECTORY_SEPARATOR);

		if (!is_dir($normalizedDirectory)) {
			mkdir($normalizedDirectory, 0777, true);
		}

		$baseCachePath = realpath($this->baseCacheDirectory);
		$targetCachePath = realpath($normalizedDirectory);

		if ($baseCachePath === false || $targetCachePath === false) {
			throw new \RuntimeException('Cache directory could not be resolved');
		}

		if (strpos($targetCachePath, $baseCachePath) !== 0) {
			throw new \RuntimeException('Cache directory must be inside ROOT . cache');
		}

		$this->cacheDirectory = $targetCachePath;
	}

	public function get() {
		$cacheFile = $this->get_cache_file_path();

		if (!is_file($cacheFile)) {
			return null;
		}

		$fileContent = file_get_contents($cacheFile);
		$payload = json_decode($fileContent, true);

		if (!is_array($payload)) {
			$this->delete();
			return null;
		}

		if (
			!isset($payload['created']) ||
			!isset($payload['expire']) ||
			!array_key_exists('data', $payload)
		) {
			$this->delete();
			return null;
		}

		$isExpired = $payload['expire'] !== 0 && time() > ($payload['created'] + $payload['expire']);

		if ($isExpired) {
			$this->delete();
			return null;
		}

		return $payload['data'];
	}

	public function save($data, ?int $expire = null): void {
		$expire = $expire ?? $this->cacheExpire;

		if ($expire === 0) {
			return;
		}

		$this->ensure_cache_directory_exists();

		$payload = [
			'created' => time(),
			'expire' => $expire,
			'data' => $data,
		];

		file_put_contents(
			$this->get_cache_file_path(),
			json_encode($payload),
			LOCK_EX
		);
	}

	public function delete(): void {
		$cacheFile = $this->get_cache_file_path();

		if (is_file($cacheFile)) {
			unlink($cacheFile);
		}
	}

	public function flush(): void {
		if (!is_dir($this->cacheDirectory)) {
			return;
		}

		$files = glob($this->cacheDirectory . DIRECTORY_SEPARATOR . '*.cache');

		foreach ($files as $filePath) {
			if (is_file($filePath)) {
				unlink($filePath);
			}
		}
	}

	private function get_cache_file_path(): string {
		return $this->cacheDirectory . DIRECTORY_SEPARATOR . $this->cacheIdentifier . '.cache';
	}

	private function ensure_cache_directory_exists(): void {
		if (!is_dir($this->cacheDirectory)) {
			mkdir($this->cacheDirectory, 0777, true);
		}
	}

	private function build_identifier($identifier): string {
		if (is_array($identifier)) {
			return implode('_', $identifier);
		}

		return (string) $identifier;
	}
}