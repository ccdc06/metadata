<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

$opt = getopt('u', ['update']);
$updateStatus = isset($opt['update']) || isset($opt['u']);

$files = listFiles();
if ($updateStatus) {
	updateIndex($files);
}
$total = count($files);

$ok = 0;
$badDetails = [];
$first = true;

foreach ($files as $yamlFn) {
	$relativeYamlFn = relativeDir($yamlFn);
	$yaml = file_get_contents($yamlFn);
	$meta = yaml_parse($yaml);

	$errors = validateMeta($meta);
	if (!empty($errors)) {
		foreach ($errors as $val) {
			list($key, $_, $err) = $val;
			if ($first) {
				echo "Key\tError\tFilename\n";
				$first = false;
			}
			echo "{$key}\t{$err}\t{$relativeYamlFn}\n";
			$badDetails[$val[2]][$relativeYamlFn] = $relativeYamlFn;
		}
	} else {
		$ok++;
	}
}

$bad = $total - $ok;

echo "\n#############################\n\n";
echo "Total	{$total}\n";
echo "OK	{$ok}\n";
echo "Bad	{$bad}\n";

foreach ($badDetails as $key => $val) {
	$count = count($val);
	echo "{$key}	{$count}\n";
}

if ($updateStatus) {
	$out[] = "# Status";
	$out[] = "|Status|Count|";
	$out[] = "|-|-|";
	$out[] = "|Total|{$total}|";
	$out[] = "|OK|{$ok}|";
	$out[] = "|Bad|{$bad}|";
	foreach ($badDetails as $key => $val) {
		$anchorKey = anchorKey($key);
		$count = count($val);
		$out[] = "|[{$key}](STATUS.md#{$anchorKey})|{$count}|";
	}

	updateReadmeStatus(implode("\n", $out));
	updateBadIndex($badDetails);
}
