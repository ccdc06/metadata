<?php
require __DIR__ . '/functions.php';

$files = listFiles();
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
			list($key, , $err) = $val;
			if ($first) {
				echo "Key\tError\tFilename\n";
				$first = false;
			}
			echo "{$key}\t{$err}\t{$relativeYamlFn}\n";
			if (empty($badDetails[$err])) {
				$badDetails[$err] = 0;
			}
			$badDetails[$val[2]]++;
		}
	} else {
		$ok++;
	}
}

$bad = $total - $ok;

echo "\n#############################\n\n";
echo "OK	{$ok}\n";
echo "Bad	{$bad}\n";

// foreach ($badDetails as $key => $val) {
// 	echo " - {$key}	{$val}\n";
// }
