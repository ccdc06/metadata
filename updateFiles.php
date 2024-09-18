<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

$baseDir = require __DIR__ . '/temp/baseDir.php';

$zip = new \ZipArchive();

foreach (streamSpecs() as $spec) {
	if (!empty($spec->Files)) {
		continue;
	}
	$fn = $baseDir . DIRECTORY_SEPARATOR . $spec->getBaseNameCbz();

	if (!file_exists($fn)) {
		echo "{$fn} not found\n";
		continue;
	}
	echo "{$fn}\n";

	$zip->open($fn);

	$spec->Files = [];
	for ($i = 0; $i < $zip->numFiles; $i++) {
		$f = $zip->getNameIndex($i);
		$pi = pathinfo($f);
		if ($pi['extension'] === 'txt' || $pi['extension'] === 'yaml' || $pi['extension'] === 'xml') {
			continue;
		}

		$spec->Files[] = $f;
	}

	$spec->save();
	$zip->close();
}
