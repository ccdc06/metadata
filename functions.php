<?php
declare(strict_types=1);
namespace Metadata;

require __DIR__ . '/valnorm.php';

function init() {
	set_time_limit(0);
	ini_set('max_execution_time', 0);
	ini_set('yaml.output_width', -1);

	if (version_compare(PHP_VERSION, '8.2.0') < 0) {
		echo "Use php >= 8.2";
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

class StatusReport {
	public int $total = 0;
	public int $ok = 0;
	public int $bad = 0;
	private array $byError = [];
	private array $byDownloadSource = [];
	private array $byDownloadUrl = [];

	public function push(Spec $spec) {
		if (empty($spec->DownloadSource)) {
			$source = '(Unknown)';
		} else {
			$source = $spec->DownloadSource;
		}

		if (empty($this->byDownloadSource[$source])) {
			$this->byDownloadSource[$source] = 0;
		}
		$this->byDownloadSource[$source]++;

		if (!empty($spec->URL)) {
			foreach ($spec->URL as $source => $val) {
				if (empty($this->byDownloadUrl[$source])) {
					$this->byDownloadUrl[$source] = 0;
				}
				$this->byDownloadUrl[$source]++;
			}
		}
	}

	public function pushOk(Spec $spec) {
		$this->ok++;
	}

	public function pushError(Spec $spec, ValidationErrors $errors) {
		$this->bad++;
		foreach ($errors->errors as $val) {
			if (empty($val->error)) {
				$error = '(unknown error)';
			} else {
				$error = $val->error;
			}

			if (empty($this->byError[$error])) {
				$this->byError[$error] = 0;
			}

			$this->byError[$error]++;
		}
	}

	public function markdown() {
		$out[] = "# Status";
		$out[] = "|Status|Count|";
		$out[] = "|-|-|";
		$out[] = "|[Total](indexes/list.csv)|{$this->total}|";
		$out[] = "|OK|{$this->ok}|";
		$out[] = "|[Errors](indexes/errors.csv)|{$this->bad}|";

		if (!empty($this->byError)) {
			$out[] = "";
			$out[] = "# [Errors](indexes/errors.csv)";
			$out[] = "|Error|Count|";
			$out[] = "|-|-|";
			foreach ($this->byError as $error => $count) {
				$out[] = "|{$error}|{$count}|";
			}
		}

		if (!empty($this->byDownloadSource)) {
			$out[] = "";
			$out[] = "# [Download sources](indexes/downloadSource.csv)";
			$out[] = "|Source|Count|";
			$out[] = "|-|-|";
			foreach ($this->byDownloadSource as $source => $count) {
				$out[] = "|{$source}|{$count}|";
			}
		}

		if (!empty($this->byDownloadUrl)) {
			$out[] = "";
			$out[] = "# [Download URLs](indexes/urlSource.csv)";
			$out[] = "|Source|Count|";
			$out[] = "|-|-|";
			foreach ($this->byDownloadUrl as $source => $count) {
				$out[] = "|{$source}|{$count}|";
			}
		}

		return implode("\n", $out) . "\n";
	}

	public function updateStatusFile() {
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
		$out[] = trim($this->markdown());
		$out[] = "\n" . $endTag;
		$out[] = $afterText;

		return file_put_contents($mdFn, implode("", $out));
	}
}

class Spec {
	public string $Title; // string
	public array $Series; // []string
	public array $Artist; // []string
	public array $Circle; // []string
	public string $Description; // string
	public array $Parody; // []string
	public array $URL; // map[string]string
	public string $URLSource; // string
	public array $Tags; // []string
	public array $Publisher; // []string
	public array $Magazine; // []string
	public array $Event; // []string
	public int $Pages; // int
	public int $Released; // int
	public array $Id; // map[string]string
	public string $DownloadSource; // string
	public int $ThumbnailIndex; // int
	public string $ThumbnailName; // string
	public array $Files; // []string
	public array $Hashes; // []string
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

	public function yaml() : string {
		return yaml_emit(json_decode(json_encode($this), true));
	}

	public function save() {
		if (empty($this->fileName)) {
			throw new \Exception("This spec was not generated from a file");
		}

		// $this->fix();

		file_put_contents($this->fileName, $this->yaml());
	}

	public function getBaseName() : string {
		if (empty($this->fileName)) {
			throw new \Exception("This file wasn't generated from a file");
		}

		return relativeDir($this->fileName);
	}

	public function DownloadSourceId() : int|string {
		if (empty($this->DownloadSource)) {
			throw new \Exception("DownloadSource not set");
		}

		if (empty($this->Id[$this->DownloadSource])) {
			throw new \Exception("Id not set for DownloadSource {$this->DownloadSource}");
		}

		return $this->Id[$this->DownloadSource];
	}

	public function DownloadSourceIdNumeric() : int {
		$id = $this->DownloadSourceId();

		if ($this->DownloadSource === 'HentaiNexus') {
			if (str_starts_with($id, '/view/')) {
				$spl = explode('/', $id);

				if (count($spl) === 3) {
					$ret = intval($spl[2]);
					if ($ret === 0) {
						throw new \Exception("Invalid HentaiNexus numeric id");
					}
					return $ret;
				}
			}
		}

		if ($this->DownloadSource === 'Schale') {
			$spl = explode('/', $id);

			if (count($spl) === 2) {
				$ret = intval($spl[0]);
				if ($ret === 0) {
					throw new \Exception("Invalid Schale numeric id");
				}

				return $ret;
			}

			if (count($spl) === 4 && $spl[1] === 'g') {
				$ret = intval($spl[2]);
				if ($ret === 0) {
					throw new \Exception("Invalid Schale numeric id");
				}

				return $ret;
			}
		}

		throw new \Exception("Invalid Id {$this->DownloadSource}: {$id}");
	}

	public function getBaseNameCbz() : string {
		$bn = $this->getBaseName();

		$pi = pathinfo($bn);
		if ($pi['extension'] !== 'yaml') {
			throw new \Exception("Unknown file {$bn}");
		}
		return mb_substr($bn, 0, -5) . '.cbz';
	}

	public function expectedCollection() : string {
		if (empty($this->DownloadSource)) {
			throw new \Exception("DownloadSource empty");
		}

		if ($this->DownloadSource === 'Schale') {
			$id = $this->DownloadSourceIdNumeric();

			$r = intval(floor($id / 1000) * 1000);

			if ($id === $r) {
				$r -= 1000;
			}

			$from = $r + 1;
			$to = $r + 1000;

			if ($id <= 14000) {
				return "anchira.to_{$from}-{$to}";
			}
			if ($id <= 24000) {
				return "koharu.to_{$from}-{$to}";
			}
			return "schale.network_{$from}-{$to}";
		}

		if ($this->DownloadSource == 'HentaiNexus') {
			$id = $this->DownloadSourceIdNumeric();

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

	public function currentCollection() : string {
		return basename(dirname($this->fileName));
	}

	public function checkCollection() {
		$expected = $this->expectedCollection();
		$current = $this->currentCollection();

		if ($current !== $expected) {
			throw new \Exception("Wrong collection. Expected: '{$expected}', Current: {$current}");
		}
	}

	public function fix() {
		$this->fixEmptyValues();
		$this->fixTitleFilenameCharacters();
		$this->fixTags();
		$this->fixArtist();
		$this->fixCircle();
		$this->fixHashes();
		$this->fixEmptyThumbnail();
		$this->fixId();
		$this->fixUrl();
	}

	public function validate() {
		$errors = new ValidationErrors();

		$this->validateTypes($errors);
		$this->validateRequired($errors);
		$this->validateIdSource($errors);
		$this->validateDownloadSource($errors);
		$this->validateUrlSource($errors);
		$this->validateTags($errors);
		$this->validateFiles($errors);
		$this->validateThumbnails($errors);

		return $errors;
	}

	public function fixEmptyValues() {
		foreach ($this as $key => $val) {
			if (is_array($val) && empty($val)) {
				unset($this->{$key});
			}

			if (is_string($val) && empty($val)) {
				unset($this->{$key});
			}
		}
	}

	public function fixTitleFilenameCharacters() {
		if (empty($this->Title)) {
			return;
		}

		$this->Title = str_replace([
			'꞉',
			// '’',
		], [
			':',
			// '\'',
		], $this->Title);
	}

	public function fixArtist() {
		if (empty($this->Artist)) {
			return;
		}

		$this->Artist = ValNorm::normalizeArtists($this->Artist);
	}

	public function fixCircle() {
		if (empty($this->Circle)) {
			return;
		}

		$this->Circle = ValNorm::normalizeCircles($this->Circle);
	}

	public function fixHashes() {
		if (empty($this->Hashes)) {
			return;
		}

		ksort($this->Hashes);
	}

	public function fixTags() {
		if (empty($this->Tags)) {
			return;
		}

		$this->Tags = ValNorm::normalizeTags($this->Tags);
	}

	public function fixEmptyThumbnail() {
		if (empty($this->Files)) {
			return;
		}

		if (!empty($this->ThumbnailName) && isset($this->ThumbnailIndex)) {
			if (!in_array($this->ThumbnailName, $this->Files)) {
				$this->ThumbnailName = $this->Files[$this->ThumbnailIndex];
			}
		}

		if (empty($this->ThumbnailName)) {
			$this->ThumbnailName = $this->Files[0];
		}

		if (!isset($this->ThumbnailIndex)) {
			$this->ThumbnailIndex = 0;
		}
	}

	public function fixId() {
		if (empty($this->Id)) {
			return;
		}

		$matches = null;

		foreach ($this->Id as $source => &$id) {
			if ($source === 'Schale') {
				if (str_starts_with($id, '/g/')) {
					continue;
				}

				if (preg_match('#^(\d+)/([a-z0-9]+)$#im', $id)) {
					$id = "/g/{$id}";
					continue;
				}

				throw new \Exception("Not implemented");
			}

			if ($source === 'HentaiNexus') {
				if (str_starts_with($id, '/view/')) {
					continue;
				}

				throw new \Exception("Not implemented");
			}
		}
	}

	public function fixUrl() {
		if (empty($this->URL)) {
			return;
		}

		foreach ($this->URL as $source => $url) {
			if ($source === 'Fakku') {
				if (str_starts_with($url, 'https://fakku.net/')) {
					$this->URL[$source] = str_replace('https://fakku.net/', 'https://www.fakku.net/', $url);
				}
			}
		}

		if (count($this->URL) === 1 && empty($this->URLSource)) {
			$this->URLSource = array_key_first($this->URL);
		}

		ksort($this->URL);
	}

	public function updateFiles(string $fn) {
		$zip = new \ZipArchive();
		if ($zip->open($fn) === false) {
			throw new \Exception();
		}

		$this->Files = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$f = $zip->getNameIndex($i);
			$pi = pathinfo($f);
			if ($pi['extension'] === 'txt' || $pi['extension'] === 'yaml' || $pi['extension'] === 'xml') {
				continue;
			}

			$this->Files[] = $f;
		}
	}

	public function validateTypes(ValidationErrors $errors) {
		foreach ($this as $key => $val) {
			$err = null;

			switch ($key) {
				case 'Artist': // []string
				case 'Series': // []string
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
				case 'URLSource': // string
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
				case 'Id': // map[string]int
				case 'Hashes': // []string
					$err = validateMapStringString($val);
					break;

				case 'fileName':
					break;

				default:
					$err = "Unknown field";
			}

			if ($err !== null) {
				$errors->push($key, $val, $err);
			}
		}
	}

	public static $requiredFeilds = [
		'Artist',
		'Files',
		'Title',
		'URL',
	];

	public function validateRequired(ValidationErrors $errors) {
		foreach (self::$requiredFeilds as $key) {
			if (empty($this->{$key})) {
				$errors->push($key, null, "Empty {$key} field");
			}
		}
	}

	public function validateIdSource(ValidationErrors $errors) {
		if (empty($this->Id)) {
			return;
		}

		foreach ($this->Id as $idKey => $idVal) {
			if (!in_array($idKey, ValNorm::$downloadSources)) {
				$errors->push("Id.{$idKey}", $idKey, "Unknown id source");
			}
		}
	}

	public function validateDownloadSource(ValidationErrors $errors) {
		if (empty($this->DownloadSource)) {
			return;
		}

		if (!in_array($this->DownloadSource, ValNorm::$downloadSources)) {
			$errors->push("DownloadSource", $this->DownloadSource, "Unknown download source");
		}
	}

	public function validateUrlSource(ValidationErrors $errors) {
		if (empty($this->URL)) {
			return;
		}

		foreach ($this->URL as $idKey => $idVal) {
			if (!in_array($idKey, ValNorm::$urlSources)) {
				$errors->push("URL.{$idKey}", $idKey, "Unknown URL source");
			}
		}

		if (empty($this->URLSource)) {
			$errors->push("URLSource", null, "Empty URL source");
		} elseif (!in_array($this->URLSource, ValNorm::$urlSources)) {
			$errors->push("URLSource", null, "Unknown URL source");
		}
	}

	public function validateTags(ValidationErrors $errors) {
		if (empty($this->Tags)) {
			return;
		}

		foreach ($this->Tags as $tag) {
			if (!is_string($tag)) {
				$errors->push("Tags", $tag, "Tag isn't a string");
			}

			if (!ValNorm::validateTag($tag)) {
				$errors->push("Tags", $tag, "Wrong tag case");
			}
		}
	}

	public function validateFiles(ValidationErrors $errors) {
		if (empty($this->Files)) {
			$errors->push("Files", null, "Empty list of files");
			return;
		}
	}

	public function validateThumbnails(ValidationErrors $errors) {
		if (empty($this->Files)) {
			return;
		}

		if (empty($this->ThumbnailName)) {
			$errors->push("ThumbnailName", null, "Empty thumbnail name");
		} elseif (!in_array($this->ThumbnailName, $this->Files)) {
			$errors->push("ThumbnailName", $this->ThumbnailName, "Thumbnail name not found in Files");
		}

		if (!isset($this->ThumbnailIndex)) {
			$errors->push("ThumbnailIndex", null, "Empty thumbnail index");
		} elseif (!isset($this->Files[$this->ThumbnailIndex])) {
			$errors->push("ThumbnailIndex", $this->ThumbnailIndex, "Thumbnail index not found in Files");
		}
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

function replacementsFn() {
	return __DIR__ . '/indexes/replacements.csv';
}

function relativeDir($path) {
	return ltrim(str_replace(baseDir(), '', $path), '/');
}

function parseMid(string $mid) : array {
	$spl = explode('/', $mid, 2);
	if (count($spl) !== 2) {
		throw new Exception('Invalid mid');
	}
	$spl[1] = '/' . $spl[1];
	return $spl;
}

function fullParseMid(string $mid) : array {
	list($source, $id) = parseMid($mid);

	if ($source === 'HentaiNexus') {
		$exp = explode('/', $mid);
		if (count($exp) !== 3) {
			throw new \Exception("bad Nexus count {$mid}");
		}
		$nid = intval($exp[2]);
	} elseif ($source === 'Schale') {
		$exp = explode('/', $mid);
		if (count($exp) !== 4) {
			throw new \Exception("bad Schale count {$mid}");
		}
		$nid = intval($exp[2]);
	}

	if (empty($nid)) {
		throw new \Exception("bad nid {$mid}");
	}
	return [$source, $id, $nid];
}

function listCollections() {
	$file = new \SplFileObject(__DIR__ . '/indexes/collections.csv');
	$file->setCsvControl(',', '"', '\\');
	$file->setFlags(\SplFileObject::READ_CSV);
	$ret = [];
	foreach ($file as $val) {
		if (!empty($val[0])) {
			$ret[] = $val[0];
		}
	}
	return $ret;
}

function listFiles() {
	$collections = listCollections();

	$files = [];
	foreach ($collections as $collection) {
		$files = array_merge($files, glob(baseDir() . "/{$collection}/*.yaml"));
	}

	natcasesort($files);

	return $files;
}

function streamSpecs(bool $reverse = false) {
	$collections = listCollections();
	natcasesort($collections);
	if ($reverse) {
		$collections = array_reverse($collections);
	}

	foreach ($collections as $collection) {
		$files = glob(baseDir() . "/{$collection}/*.yaml");
		natcasesort($files);
		if ($reverse) {
			$files = array_reverse($files);
		}

		foreach ($files as $file) {
			yield Spec::fromFile($file);
		}
	}
}

function buildEmptyUrlCache() {
	$cacheFn = __DIR__ . '/temp/emptyUrlCache.json';

	$files = listFiles();

	$i = 0;
	$count = count($files);

	$missing = [];
	foreach ($files as $yamlFn) {
		$i++;
		if ($i % 1000 === 0) {
			echo "{$i}/{$count}\n";
		}
		$rFile = relativeDir($yamlFn);

		$yaml = file_get_contents($yamlFn);
		$meta = yaml_parse($yaml);
		if (empty($meta)) {
			continue;
		}

		if (!empty($meta['URL'])) {
			continue;
		}

		$missing[] = $rFile;
	}

	file_put_contents($cacheFn, json_encode($missing, JSON_PRETTY_PRINT));
}

function getEmptyUrlsCache() {
	$cacheFn = __DIR__ . '/temp/emptyUrlCache.json';
	$files = json_decode(file_get_contents($cacheFn), true);

	$hideFn = __DIR__ . '/temp/hidden.json';
	if (file_exists($hideFn)) {
		$hide = json_decode(file_get_contents($hideFn), true);
	} else {
		$hide = [];
	}

	$missing = [];
	foreach ($files as $yamlFn) {
		$rFile = relativeDir($yamlFn);
		if (!empty($hide[$rFile])) {
			continue;
		}
		$fn = baseDir() . '/' . $yamlFn;
		$spec = Spec::fromFile($fn);

		if (!empty($spec->URL)) {
			continue;
		}

		$missing[$rFile] = $spec;
	}
	return $missing;
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

function h($str) {
	return htmlspecialchars($str);
}
