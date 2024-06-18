<?php
require __DIR__ . '/functions.php';

$opt = getopt('u', ['update']);
$update = isset($opt['u']) || isset($opt['update']);

$files = listFiles();

foreach ($files as $yamlFn) {
	$oldYaml = file_get_contents($yamlFn);
	$meta = yaml_parse($oldYaml);

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
