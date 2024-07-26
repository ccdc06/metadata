<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

$opt = getopt('u', ['update']);
$update = isset($opt['u']) || isset($opt['update']);

$files = listFiles();

foreach ($files as $yamlFn) {
	$spec = Spec::fromFile($yamlFn);
	$spec->checkCollection();
}

if (!$update) {
	echo "WARNING: Files won't be changed unless you pass -u or --update flag\n";
}
