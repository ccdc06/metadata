<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();
ini_set("memory_limit", -1);

$zip = new \ZipArchive();
$baseDir = require __DIR__ . '/temp/baseDir.php';

$lfd = __DIR__ . '/temp/lastFile';
if (!is_dir($lfd)) {
	mkdir($lfd, 0777, true);
}

foreach (streamSpecs() as $spec) {
	$fn = $baseDir . DIRECTORY_SEPARATOR . $spec->getBaseNameCbz();
	if (!file_exists($fn)) {
		continue;
	}
	echo $fn, PHP_EOL;

	$zip->open($fn);
	$sf = end($spec->Files);
	if ($zip->statName($sf) === false) {
		throw new Exception("BADFILES");
	}
	$pi = pathinfo($sf);
	$lastFileName = $lfd . DIRECTORY_SEPARATOR . md5($spec->getBaseName()) . '.' . $pi['extension'];
	if (file_exists($lastFileName)) {
		continue;
	}
	file_put_contents($lastFileName, $zip->getStreamName($sf));

	$zip->close();
}
