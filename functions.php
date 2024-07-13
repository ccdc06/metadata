<?php
namespace Metadata;

function init() {
	set_time_limit(0);
	ini_set('max_execution_time', 0);
	ini_set('yaml.output_width', -1);

	if (version_compare(PHP_VERSION, '8.3.0') < 0) {
		echo "Use php >= 8.3";
		exit(__LINE__);
	}

	if (!extension_loaded('yaml')) {
		echo "PHP extension 'yaml' is required";
		exit(__LINE__);
	}

	if (!is_dir(__DIR__ . '/temp')) {
		mkdir(__DIR__ . '/temp', 0777, true);
	}
}

class Lists {
	static $downloadSources = [
		'Anchira',
		'Koharu',
		'HentaiNexus',
	];

	static $urlSources = [
		'Fakku',
		'Irodori',
		'ProjectHentai',
		'FuDeORS',
	];

	static $tagLowercaseExceptions = [
		"bdsm" => "BDSM",
		"bl" => "BL",
		"bss" => "BSS",
		"cg set" => "CG Set",
		"fffm foursome" => "FFFM Foursome",
		"ffm threesome" => "FFM Threesome",
		"mmf threesome" => "MMF Threesome",
		"mmmf foursome" => "MMMF Foursome",
		"ntr" => "NTR",
		"romance-centric" => "Romance-centric",
		"slice of life" => "Slice of Life",
		"valentine-sale" => "Valentine-sale",
		"x-ray" => "X-ray",
	];
}

class Spec {
	public string $Title; // string
	public array $Artist; // []string
	public array $Circle; // []string
	public string $Description; // string
	public array $Parody; // []string
	public array $URL; // map[string]string
	public array $Tags; // []string
	public array $Publisher; // []string
	public array $Magazine; // []string
	public array $Event; // []string
	public int $Pages; // int
	public int $Released; // int
	public array $Id; // map[string]IntOrString
	public string $DownloadSource; // string
	public int $ThumbnailIndex; // int
	public string $ThumbnailName; // string
	public array $Files; // []string
	private string $fileName;

	public static function fromFile(string $fn) : Spec {
		if (!file_exists($fn)) {
			throw new \Exception("Spec {$fn} not found");
		}

		$data = file_get_contents($fn);

		$ret = yamlDecodeIntoClass($data, Spec::class);
		$ret->fileName = $fn;
		return $ret;
	}

	public function getBaseName() : string {
		if (empty($this->fileName)) {
			throw new \Exception("This file wasn't generated from a file");
		}

		return relativeDir($this->fileName);
	}

	public function getBaseNameCbz() : string {
		$bn = $this->getBaseName();

		$pi = pathinfo($bn);
		if ($pi['extension'] !== 'yaml') {
			throw new \Exception("Unknown file {$bn}");
		}
		return mb_substr($bn, 0, -5) . '.cbz';
	}
}

function yamlDecodeIntoClass($data, $class) {
	$ret = new $class;

	$yaml = yaml_parse($data);

	foreach ($yaml as $key => $val) {
		if (property_exists($ret, $key)) {
			$ret->{$key} = $val;
		}
	}

	return $ret;
}

function fixSlashes($str) {
	return str_replace(['\\', '/'], '/', $str);
}

function baseDir() {
	return fixSlashes(__DIR__);
}

function relativeDir($path) {
	return ltrim(str_replace(baseDir(), '', $path), '/');
}

function listCollections($opts = []) {
	$file = new \SplFileObject(__DIR__ . '/indexes/collections.csv');
	$file->setFlags(\SplFileObject::READ_CSV);
	$ret = [];
	foreach ($file as $val) {
		if (!empty($val[0])) {
			$ret[] = $val[0];
		}
	}
	return $ret;
}

function listFiles(...$opts) {
	$collections = listCollections();

	if (in_array('noAnchira', $opts)) {
		$collections = array_values(array_filter($collections, function ($val) {
			return !str_starts_with($val, 'anchira.to_');
		}));
	}

	if (in_array('schaleOnly', $opts)) {
		$collections = array_values(array_filter($collections, function ($val) {
			return str_starts_with($val, 'anchira.to_') || str_starts_with($val, 'koharu.to_');
		}));
	}

	$files = [];
	foreach ($collections as $collection) {
		$files = array_merge($files, glob(baseDir() . "/{$collection}/*.yaml"));
	}

	natsort($files);

	return $files;
}

function fixEmptyValues(&$meta) {
	$meta = array_filter($meta, function ($val) {
		if (is_array($val) && empty($val)) {
			return false;
		}

		if (is_string($val) && empty($val)) {
			return false;
		}

		return true;
	});
}

function generateCollectionName($meta) {
	if (empty($meta['DownloadSource'])) {
		throw new \Exception("DownloadSource empty");
	}

	if ($meta['DownloadSource'] == 'Anchira') {
		if (empty($meta['Id']['Anchira'])) {
			throw new \Exception("Anchira Id empty");
		}
		$id = $meta['Id']['Anchira'];

		$r = intval(floor($id / 1000) * 1000);

		if ($id === $r) {
			$r -= 1000;
		}

		$from = $r + 1;
		$to = $r + 1000;
		return "anchira.to_{$from}-{$to}";
	}

	if ($meta['DownloadSource'] == 'HentaiNexus') {
		if (empty($meta['Id']['HentaiNexus'])) {
			throw new \Exception("HentaiNexus Id empty");
		}

		$id = $meta['Id']['HentaiNexus'];
		if ($id <= 17000) {
			$from = 1;
			$to = 17000;
		} else {
			$r = intval(floor($id / 1000) * 1000);

			if ($id === $r) {
				$r -= 1000;
			}

			$from = $r + 1;
			$to = $r + 1000;
		}

		return "hentainexus.com_{$from}-{$to}";
	}
}

function fillEmptyThumbnail(&$meta) {
	if (empty($meta['Files'])) {
		return;
	}

	if (empty($meta['ThumbnailName'])) {
		$meta['ThumbnailName'] = $meta['Files'][0];
	}

	if (!isset($meta['ThumbnailIndex'])) {
		$meta['ThumbnailIndex'] = 0;
	}
}

function reorderFields(&$meta) {
	$order = array_flip([
		'Title',
		'Artist',
		'Circle',
		'Description',
		'Parody',
		'URL',
		'Tags',
		'Publisher',
		'Magazine',
		'Event',
		'Pages',
		'Released',
		'Id',
		'DownloadSource',
		'ThumbnailIndex',
		'ThumbnailName',
		'Files',
	]);

	uksort($meta, function ($a, $b) use ($order) {
		if (!isset($order[$a])) {
			throw new \Exception("Unknown field {$a}");
		}

		if (!isset($order[$b])) {
			throw new \Exception("Unknown field {$b}");
		}

		return $order[$a] <=> $order[$b];
	});
}

function validateMeta($meta) {
	$errors = [];

	foreach ($meta as $key => $val) {
		$err = null;

		switch ($key) {
			case 'Artist': // []string
			case 'Circle': // []string
			case 'Magazine': // []string
			case 'Parody': // []string
			case 'Tags': // []string
			case 'Publisher': // []string
			case 'Event': // []string
			case 'Files': // []string
				$err = validateArrayString($val);
				break;

			case 'Title': // string
			case 'Description': // string
			case 'ThumbnailName': // string
			case 'DownloadSource': // string
				if (!is_string($val)) {
					$err = "Not a string";
				}
				break;

			case 'ThumbnailIndex': // int
			case 'Pages': // int
			case 'Released': // int
				if (!is_int($val)) {
					$err = "Not an int";
				}
				break;

			case 'URL': // map[string]string
				$err = validateMapStringString($val);
				break;

			case 'Id': // map[string]int
				$err = validateMapStringIntOrString($val);

				break;

			default:
				$err = "Unknown field";
		}

		if ($err) {
			$errors[] = [$key, $val, $err];
		}
	}

	if (!empty($meta['Id']) && is_array($meta['Id'])) {
		foreach ($meta['Id'] as $idKey => $idVal) {
			if (!in_array($idKey, Lists::$downloadSources)) {
				$errors[] = ["Id.{$idKey}", $idKey, "Unknown id source"];
			}
		}
	}

	if (!empty($meta['DownloadSource']) && is_string($meta['DownloadSource'])) {
		if (!in_array($meta['DownloadSource'], Lists::$downloadSources)) {
			$errors[] = ["DownloadSource", $meta['DownloadSource'], "Unknown download source"];
		}
	}

	if (!empty($meta['URL']) && is_array($meta['URL'])) {
		foreach ($meta['URL'] as $idKey => $idVal) {
			if (!in_array($idKey, Lists::$urlSources)) {
				$errors[] = ["URL.{$idKey}", $idKey, "Unknown URL source"];
			}
		}
	}

	if (!empty($meta['Tags']) && is_array($meta['Tags'])) {
		foreach ($meta['Tags'] as $tag) {
			if (is_string($tag)) {
				if (!validateLowercaseTag($tag)) {
					$errors[] = ["Tags", $tag, "Lowercase tag"];
				}
			}
		}
	}

	if (empty($meta['Files'])) {
		$errors[] = ["Files", null, "Empty list of files"];
	} else {
		if (is_array($meta['Files'])) {
			if (empty($meta['ThumbnailName'])) {
				$errors[] = ["ThumbnailName", $meta['ThumbnailName'], "Empty thumbnail name"];
			} elseif (!in_array($meta['ThumbnailName'], $meta['Files'])) {
				$errors[] = ["ThumbnailName", $meta['ThumbnailName'], "Thumbnail name not found in Files"];
			}

			if (!isset($meta['ThumbnailIndex'])) {
				$errors[] = ["ThumbnailIndex", $meta['ThumbnailName'], "Empty thumbnail index"];
			} elseif (!isset($meta['Files'][$meta['ThumbnailIndex']])) {
				$errors[] = ["ThumbnailIndex", $meta['ThumbnailIndex'], "Thumbnail index not found in Files"];
			}
		}
	}

	$required = [
		'Artist',
		'Files',
		'Title',
		'URL',
	];

	foreach ($required as $key) {
		if (empty($meta[$key])) {
			$errors[] = [$key, null, "Empty {$key} field"];
		}
	}

	$copy = $meta;
	reorderFields($copy);
	if ($copy !== $meta) {
		$errors[] = [null, null, "Wrong field order"];
	}

	return $errors;
}

function validateArrayString($var) {
	if (!is_array($var)) {
		return "Not an array";
	}

	if ($var !== array_values($var)) {
		return "Not a sequentially indexed array";
	}

	foreach ($var as $key => $val) {
		if (!is_string($val)) {
			return "One or more values of the array aren't string";
		}
	}
}

function validateMapStringString($var) {
	if (!is_array($var)) {
		return "Not a map";
	}

	foreach ($var as $key => $val) {
		if (!is_string($key)) {
			return "One or more keys of the map aren't string";
		}

		if (!is_string($val)) {
			return "One or more values of the map aren't string";
		}
	}
}

function validateMapStringInt($var) {
	if (!is_array($var)) {
		return "Not a map";
	}

	foreach ($var as $key => $val) {
		if (!is_string($key)) {
			return "One or more keys of the map aren't string";
		}

		if (!is_int($val)) {
			return "One or more values of the map aren't int";
		}
	}
}

function validateMapStringIntOrString($var) {
	if (!is_array($var)) {
		return "Not a map";
	}

	foreach ($var as $key => $val) {
		if (!is_string($key) && !is_int($val)) {
			return "One or more keys of the map aren't int or string";
		}
	}
}

function anchorKey($text) {
	$text = mb_convert_case($text, MB_CASE_LOWER);
	return trim(preg_replace('#\PL+#im', '-', $text), '-');
}

function updateReadmeStatus($text) {
	$beginTag = '<!-- [Status] -->';
	$endTag = '<!-- [/Status] -->';

	$mdFn = __DIR__ . '/README.md';

	$md = file_get_contents($mdFn);

	$beginPos = strpos($md, $beginTag);
	$beforeText = substr($md, 0, $beginPos);

	$endPos = strpos($md, $endTag, $beginPos) + strlen($endTag);
	$afterText = substr($md, $endPos);

	$out[] = $beforeText;
	$out[] = $beginTag . "\n";
	$out[] = $text;
	$out[] = "\n" . $endTag;
	$out[] = $afterText;

	return file_put_contents($mdFn, implode("", $out));
}

function updateBadIndex($tag) {
	$badFn = __DIR__ . '/indexes/bad.csv';
	$out = new \SplFileObject($badFn, 'w');

	foreach ($tag as $status => $val) {
		foreach ($val as $fn) {
			$out->fputcsv([$fn, $status]);
		}
	}
}

function updateIndex($files) {
	$out = new \SplFileObject(__DIR__ . '/indexes/list.csv', 'w');

	foreach ($files as $yamlFn) {
		$relativeYamlFn = relativeDir($yamlFn);

		$pi = pathinfo($relativeYamlFn);
		if ($pi['extension'] !== 'yaml') {
			throw new \Exception("Unknown file {$relativeYamlFn}");
		}

		$cbzName = mb_substr($relativeYamlFn, 0, -5) . '.cbz';

		$out->fputcsv([$relativeYamlFn, $cbzName]);
	}
}

function fixLowercaseTag($tag) {
	$lc = mb_strtolower($tag);

	if (isset(Lists::$tagLowercaseExceptions[$lc])) {
		return Lists::$tagLowercaseExceptions[$lc];
	}

	$new = mb_convert_case($tag, MB_CASE_TITLE, 'UTF-8');
	return $new;
}

function validateLowercaseTag($tag) {
	$lc = mb_strtolower($tag);

	if (isset(Lists::$tagLowercaseExceptions[$lc])) {
		return Lists::$tagLowercaseExceptions[$lc] === $tag;
	}

	return mb_convert_case($tag, MB_CASE_TITLE, 'UTF-8') === $tag;
}

function h($str) {
	return htmlspecialchars($str);
}
