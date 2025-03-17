<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

$files = listFiles();

$out = [];

foreach ($files as $yamlFn) {
	$spec = Spec::fromFile($yamlFn);

	if (empty($spec->Artist)) {
		continue;
	}

	if (count($spec->Artist) !== 1) {
		continue;
	}

	$out[] = ["{$spec->Artist[0]}	{$spec->Title}", $spec->Artist[0], $spec->Title, !empty($spec->Series)];
}

usort($out, function ($a, $b) {
	return strnatcasecmp($a[2], $b[2]);
	return strnatcasecmp($a[0], $b[0]);
})

?>
<!DOCTYPE html>
<html>
	<head>
		<title></title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	</head>
	<body>
		<div class="container">
			<table class="table table-sm table-bordered table-hover">
				<thead>
					<tr>
						<th>Artist</th>
						<th>Title</th>
						<th style="width: 0"></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($out as list(, $artist, $name, $hasSeries)): ?>
					<?php
					$c = implode("\n", [
						"[",
						"		\"title\" => \"" . addslashes($name) . "\",",
						"		\"conditions\" => [",
						"			\"Artist\" => [[\"match\", \"" . addslashes($artist) . "\"]],",
						"			\"Title\" => [[\"prefix\", \"" . addslashes($name) . "\"]],",
						"		],",
						"	],",
					]);
					?>
					<tr class="<?= $hasSeries ? 'table-secondary' : '' ?>">
						<td><?= htmlspecialchars($artist) ?></td>
						<td><?= htmlspecialchars($name) ?></td>
						<td><button type="button" class="btn btn-sm btn-primary" onclick="navigator.clipboard.writeText(<?= htmlspecialchars(json_encode(str_replace("\'", "'", $c))) ?>)">Copy</button> <?php
						?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</body>
</html>
