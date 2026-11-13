<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

$baseDir = require __DIR__ . '/temp/baseDir.php';

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

	try {
		$spec->updateFiles($fn);
	} catch (\Exception $e) {
		echo $e->getMessage(), "\n";
		continue;
	}

	$spec->save();
}
