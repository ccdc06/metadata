<?php
if (php_sapi_name() !== 'cli') {
	echo "For command line usage only";
	exit(__LINE__);
}

if (version_compare(PHP_VERSION, '8.3.0') < 0) {
	echo "Use php >= 8.3";
	exit(__LINE__);
}

if (!extension_loaded('yaml')) {
	echo "PHP extension 'yaml' is required";
	exit(__LINE__);
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

function listFiles() {
	$files = [];
	$files = array_merge($files, glob(baseDir() . '/anchira.to_*/*.yaml'));
	$files = array_merge($files, glob(baseDir() . '/hentainexus.com_*/*.yaml'));
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
		'Thumbnail',
		'Released',
		'Id',
		'DownloadSource',
		'ThumbnailIndex',
		'ThumbnailName',
		'Files',
	]);

	uksort($meta, function ($a, $b) use ($order) {
		if (!isset($order[$a])) {
			throw new Exception("Unknown field {$a}");
		}

		if (!isset($order[$b])) {
			throw new Exception("Unknown field {$b}");
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
				$err = validateMapStringInt($val);
				break;

			default:
				$err = "Unknown field";
		}

		if ($err) {
			$errors[] = [$key, $val, $err];
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
