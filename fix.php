<?php
namespace Metadata;
require __DIR__ . '/functions.php';

$opt = getopt('u', ['update']);
$update = isset($opt['u']) || isset($opt['update']);

$args = [];
foreach ($argv as $key => $val) {
	if ($key === 0) {
		continue;
	}

	if (substr($val, 0, 1) === '-') {
		continue;
	}

	$args[$val] = true;
}

if (empty($args)) {
	$args['all'] = true;
}

$files = listFiles();

foreach ($files as $yamlFn) {
	$oldYaml = file_get_contents($yamlFn);
	$meta = yaml_parse($oldYaml);

	// Empty values
	if (!empty($args['empty']) || !empty($args['all'])) {
		fixEmptyValues($meta);
	}

	// Lowercase Tags
	if (!empty($args['lctags']) || !empty($args['all'])) {
		if (!empty($meta['Tags']) && is_array($meta['Tags'])) {
			foreach ($meta['Tags'] as &$tag) {
				if (is_string($tag)) {
					$tag = fixLowercaseTag($tag);
				}
			}
			unset($tag);
		}
	}

	// Order
	if (!empty($args['order']) || !empty($args['all'])) {
		reorderFields($meta);
	}

	$newYaml = yaml_emit($meta);
	if ($newYaml !== $oldYaml) {
		if ($update) {
			file_put_contents($yamlFn, $newYaml);
		}
		echo $yamlFn, PHP_EOL;
	}
}

if (!$update) {
	echo "WARNING: Files won't be changed unless you pass -u or --update flag\n";
}
