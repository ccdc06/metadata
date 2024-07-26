<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

$baseDir = require __DIR__ . '/temp/baseDir.php';

foreach (streamSpecs() as $spec) {
	$fn = $baseDir . DIRECTORY_SEPARATOR . $spec->getBaseNameCbz();
	echo "{$fn}\n";
	if (file_exists($fn)) {
		$save = false;
		if (empty($spec->Hashes['SHA256'])) {
			$save = true;
			$spec->Hashes['SHA256'] = hash_file('sha256', $fn);
		}

		if (empty($spec->Hashes['LANraragi'])) {
			$save = true;
			$file = new \SplFileObject($fn);
			$bytes = $file->fread(512000);
			$spec->Hashes['LANraragi'] = sha1($bytes);
		}

		if ($save) {
			$spec->save();
		}
	}
}
