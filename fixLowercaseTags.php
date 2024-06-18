<?php
require __DIR__ . '/functions.php';

$files = listFiles();

foreach ($files as $yamlFn) {
	$oldYaml = file_get_contents($yamlFn);
	$meta = yaml_parse($oldYaml);

	$changed = false;
	if (!empty($meta['Tags']) && is_array($meta['Tags'])) {
		foreach ($meta['Tags'] as &$tag) {
			if (is_string($tag)) {
				$lc = mb_strtolower($tag);
				if (isset(Lists::$tagLowercaseExceptions[$lc])) {
					$tag = Lists::$tagLowercaseExceptions[$lc];
					continue;
				}

				$new = mb_convert_case($tag, MB_CASE_TITLE, 'UTF-8');
				if ($tag !== $new) {
					$tag = $new;
					$changed = true;
				}
			}
		}
		unset($tag);
	}

	$newYaml = yaml_emit($meta);

	if ($newYaml !== $oldYaml) {
		// file_put_contents($yamlFn, $newYaml);
		echo $yamlFn, PHP_EOL;
	}
}
