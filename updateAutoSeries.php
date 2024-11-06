<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

$autoSeries = require __DIR__ . '/arrays/autoSeriesMap.php';

$i = 0;
foreach (streamSpecs() as $spec) {
	$i++;
	if ($i % 1000 === 0) {
		echo $i, PHP_EOL;
	}

	if (!empty($spec->Series)) {
		continue;
	}

	$save = false;
	foreach ($autoSeries as $series) {
		$seriesTitle = $series['title'];
		$conditions = $series['conditions'];

		$specMatch = true;
		foreach ($conditions as $field => $rules) {
			if (empty($spec->$field)) {
				continue;
			}

			if (!is_array($spec->$field)) {
				$fieldValues = [$spec->$field];
			} else {
				$fieldValues = $spec->$field;
			}

			foreach ($fieldValues as $fieldValue) {
				foreach ($rules as $ruleType => $ruleValue) {
					// if (str_starts_with($spec->$field, '48')) {
					// 	var_dump($fieldValue, $ruleValue);
					// 	exit;
					// }
					switch($ruleType) {
						case 'prefix':
							if (!str_starts_with($fieldValue, $ruleValue)) {
								$specMatch = false;
								break 4;
							}
							break;

						case 'match':
							if (strcasecmp($fieldValue, $ruleValue) !== 0) {
								$specMatch = false;
								break 4;
							}
							break;

						case 'contains':
							if (!str_contains($fieldValue, $ruleValue)) {
								$specMatch = false;
								break 4;
							}
							break;

						default:
							throw new \Exception("unknown rule type {$ruleType}");
					}
				}
			}
		}

		if ($specMatch) {
			echo "{$seriesTitle} => {$spec->getBaseName()}\n";
			$spec->Series[] = $seriesTitle;
			$save = true;
			break;
		}
	}

	if ($save) {
		$spec->save();
	}
}
