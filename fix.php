<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

// $spec = Spec::fromFile(__DIR__ . "\anchira.to_7001-8000\[Minato Itoya] Until Kashiwagura-san Falls (Comic Bavel 2021-11).yaml");
// $spec->fixAutoSeries();
// var_dump($spec);
// exit;


$opt = getopt('u', ['update']);
$update = isset($opt['u']) || isset($opt['update']);

$files = listFiles();

$count = count($files);
$i = 0;
foreach ($files as $yamlFn) {
	$i++;
	if ($i % 1000 === 0) {
		echo "{$i}/{$count}\n";
	}
	$oldYaml = file_get_contents($yamlFn);
	$spec = Spec::fromFile($yamlFn);

	$spec->fix();

	$newYaml = $spec->yaml();
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
