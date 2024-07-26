<?php
declare(strict_types=1);
namespace Metadata;

class ValNorm {
	public static array $downloadSources = [];
	public static array $urlSources = [];
	public static array $tags = [];
	public static array $artists = [];
	public static array $circles = [];

	public static function validateTag(string $tag) : bool {
		$lc = mb_strtolower($tag);

		if (isset(self::$tags[$lc])) {
			return self::$tags[$lc] === $tag;
		}

		return mb_convert_case($tag, MB_CASE_TITLE, 'UTF-8') === $tag;
	}

	public static function normalizeTag(string $tag) : string {
		$lc = mb_strtolower($tag);

		if (isset(self::$tags[$lc])) {
			$tag = self::$tags[$lc];
		} else {
			$tag = mb_convert_case($tag, MB_CASE_TITLE, 'UTF-8');
		}
		return $tag;
	}

	public static function normalizeTags(array $tags) : array {
		foreach ($tags as &$tag) {
			$tag = self::normalizeTag($tag);
		}
		natcasesort($tags);
		return array_values($tags);
	}

	public static function normalizeArtist(string $artist) : string {
		$lc = mb_strtolower($artist);

		if (isset(self::$artists[$lc])) {
			return self::$artists[$lc];
		}
		return mb_convert_case($artist, MB_CASE_TITLE, 'UTF-8');
	}

	public static function normalizeArtists(array $artists) : array {
		foreach ($artists as &$artist) {
			$artist = self::normalizeArtist($artist);
		}
		natcasesort($artists);
		return array_values($artists);
	}

	public static function normalizeCircle(string $circle) : string {
		$lc = mb_strtolower($circle);

		if (isset(self::$circles[$lc])) {
			return self::$circles[$lc];
		}
		return mb_convert_case($circle, MB_CASE_TITLE, 'UTF-8');
	}

	public static function normalizeCircles(array $circles) : array {
		foreach ($circles as &$circle) {
			$circle = self::normalizeCircle($circle);
		}
		natcasesort($circles);
		return array_values($circles);
	}
}
ValNorm::$downloadSources = require __DIR__ . '/arrays/downloadSources.php';
ValNorm::$urlSources = require __DIR__ . '/arrays/urlSources.php';
ValNorm::$tags = require __DIR__ . '/arrays/tagsMap.php';
ValNorm::$artists = require __DIR__ . '/arrays/artistsMap.php';
ValNorm::$circles = require __DIR__ . '/arrays/circlesMap.php';

class ValidationErrors implements \Countable {
	public array $errors = [];

	public function count() : int {
		return count($this->errors);
	}

	public function empty() : bool {
		return empty($this->errors);
	}

	public function push(string $key, $value, string $error) {
		$p = new ValidationError();

		if (!empty($key)) {
			$p->key = $key;
		}

		if (!empty($value)) {
			$p->value = $value;
		}

		if (!empty($error)) {
			$p->error = $error;
		}

		$this->errors[] = $p;
	}

	public function tsv(...$extra) : string {
		$ret = '';
		foreach ($this->errors as $error) {
			$ret .= $error->tsv(...$extra);
		}
		return $ret;
	}
}

class ValidationError {
	public string $key;
	public $value;
	public string $error;
	public function tsv(...$extra) {
		return implode("\t", [$this->key, $this->error, ...$extra]) . "\n";
	}
}
