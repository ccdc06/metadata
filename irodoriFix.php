<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();
ini_set("memory_limit", -1);

$zip = new \ZipArchive();
$baseDir = require __DIR__ . '/temp/baseDir.php';

foreach (streamSpecs() as $spec) {
	$fn = $baseDir . DIRECTORY_SEPARATOR . $spec->getBaseNameCbz();
	if (!file_exists($fn)) {
		continue;
	}

	echo $spec->getBaseName(), "\t", $fn, "\t";

	try {
		$zip->open($fn);
		$sf = end($spec->Files);
		if ($zip->statName($sf) === false) {
			$spec->updateFiles($fn);
			$spec->save();
			$sf = end($spec->Files);
			echo "BADFILES,";
		}
		$lastFile = $zip->getFromName($sf);
		$zip->close();

		$im = imagecreatefromstring($lastFile);
		$im2 = imagecreatetruecolor(1, 1);
		imagecopyresampled($im2, $im, 0, 0, imagesx($im) - 32, imagesy($im) - 128, 1, 1, 32, 32);
		$colorA = imagecolorsforindex($im2, imagecolorat($im2, 0, 0));
	} catch (\Throwable $e) {
		echo $e->getMessage(), PHP_EOL;
		echo "ERROR	{$sf}	{$fn}", PHP_EOL;
		exit;
	}


	$dr = abs($colorA['red'] - 65);
	$dg = abs($colorA['green'] - 65);
	$db = abs($colorA['blue'] - 255);
	$isIrodori = $dr < 5 && $dg < 5 && $db < 5;

	if ($isIrodori && empty($spec->URL['Irodori'])) {
		echo "NIS_IRO";
	}

	if (!$isIrodori && !empty($spec->URL['Irodori'])) {
		echo "NOT_IRO";
	}

	echo PHP_EOL;
}
