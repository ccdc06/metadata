<?php
require __DIR__ . '/functions.php';

$files = listFiles();

foreach ($files as $yamlFn) {
	$oldYaml = file_get_contents($yamlFn);
	$meta = yaml_parse($oldYaml);

	$newYaml = yaml_emit($meta);
	if ($newYaml !== $oldYaml) {
		file_put_contents($yamlFn, $newYaml);
		echo $yamlFn, PHP_EOL;
	}
}
