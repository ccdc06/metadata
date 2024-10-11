<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

$opt = getopt('u', ['update']);
$updateStatus = isset($opt['update']) || isset($opt['u']);

if ($updateStatus) {
	$listIndex = new \SplFileObject( __DIR__ . '/indexes/list.csv', 'w');
	$listIndex->fputcsv(['file', 'gallery']);

	$byErrorIndex = new \SplFileObject( __DIR__ . '/indexes/errors.csv', 'w');
	$byErrorIndex->fputcsv(['error', 'file']);

	$byDownloadSourceIndex = new \SplFileObject( __DIR__ . '/indexes/downloadSource.csv', 'w');
	$byDownloadSourceIndex->fputcsv(['source', 'id', 'file']);

	$byUrlSourceIndex = new \SplFileObject( __DIR__ . '/indexes/urlSource.csv', 'w');
	$byUrlSourceIndex->fputcsv(['source', 'url', 'file']);

	$byTitleIndex = new \SplFileObject( __DIR__ . '/indexes/title.csv', 'w');
	$byTitleIndex->fputcsv(['title', 'file']);

	$byTagIndex = new \SplFileObject( __DIR__ . '/indexes/tags.csv', 'w');
	$byTagIndex->fputcsv(['tag', 'count']);
	$byTagArray = [];
}

$files = listFiles();
$status = new StatusReport();
$status->total = count($files);

$first = true;
foreach ($files as $yamlFn) {
	$spec = Spec::fromFile($yamlFn);
	$baseName = $spec->getBaseName();
	$title = $spec->Title;

	if ($updateStatus) {
		$baseNameCbz = $spec->getBaseNameCbz();
		$downloadSource = $spec->DownloadSource;
		$downloadSourceId = $spec->DownloadSourceId();
		$listIndex->fputcsv([$baseName, $baseNameCbz]);
		$byDownloadSourceIndex->fputcsv([$downloadSource, $downloadSourceId, $baseName]);

		if (!empty($spec->URL)) {
			foreach ($spec->URL as $source => $url) {
				$byUrlSourceIndex->fputcsv([$source, $url, $baseName]);
			}
		}
		$byTitleIndex->fputcsv([$title, $baseName]);

		if (!empty($spec->Tags)) {
			foreach ($spec->Tags as $tag) {
				if (empty($byTagArray[$tag])) {
					$byTagArray[$tag] = 0;
				}
				$byTagArray[$tag]++;
			}
		}
	}

	$errors = $spec->validate();

	$status->push($spec);
	if ($errors->empty()) {
		$status->pushOk($spec);
	} else {
		$status->pushError($spec, $errors);
		if ($first) {
			echo "Key\tError\tFilename\n";
			$first = false;
		}
		echo $errors->tsv($baseName);
		if ($updateStatus) {
			foreach ($errors->errors as $error) {
				$byErrorIndex->fputcsv([$error->error, $baseName]);
			}
		}
	}
}

if ($updateStatus) {
	uksort($byTagArray, 'strnatcasecmp');
	foreach ($byTagArray as $tag => $count) {
		$byTagIndex->fputcsv([$tag, strval($count)]);
	}

	$status->updateStatusFile();
}
