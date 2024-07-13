<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

$opt = getopt('u', ['update']);
$update = isset($opt['u']) || isset($opt['update']);

$files = listFiles();

foreach ($files as $yamlFn) {
	$oldYaml = file_get_contents($yamlFn);
	$meta = yaml_parse($oldYaml);

	$current = basename(dirname($yamlFn));
	$expected = generateCollectionName($meta);

	if ($current !== $expected) {
		if ($update) {
			throw new \Exception("Not implemented");
		}
		echo $yamlFn, PHP_EOL;
	}
}

if (!$update) {
	echo "WARNING: Files won't be changed unless you pass -u or --update flag\n";
}
