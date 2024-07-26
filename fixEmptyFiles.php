<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

$opt = getopt('u', ['update']);
$update = isset($opt['u']) || isset($opt['update']);

$files = listFiles();

$zip = new \ZipArchive();
foreach ($files as $yamlFn) {
	$spec = Spec::fromFile($yamlFn);
	if (!empty($spec->Files)) {
		continue;
	}
	$fn = 'P:/' . $spec->getBaseNameCbz();

	if (file_exists($fn)) {
		$zip->open($fn);

		$ret = [];
		for($i = 0; $i < $zip->numFiles; $i++) {
			$f = $zip->getNameIndex($i);
			if ($f !== 'info.yaml') {
				$ret[] = $f;
			}
		}

		var_dump($ret);
		unset($spec->files);
		$spec->Files = $ret;

		if ($update) {
			$spec->save();
		}
	}

}

if (!$update) {
	echo "WARNING: Files won't be changed unless you pass -u or --update flag\n";
}
