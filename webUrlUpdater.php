<?php
namespace Metadata;
require __DIR__ . '/functions.php';
init();

switch (php_sapi_name()) {
	case 'cli': return routeCli();
	case 'cli-server':
		switch ($_SERVER['SCRIPT_NAME']) {
			case '/': case '/index.php': return routeWebIndex();
			case '/update.php': return routeWebUpdate();
			case '/hide.php': return routeWebHide();
			case '/hentagProxy.php': return routeWebHentagProxy();
			case '/sm.php': return routeSm();
			case '/duplicates.php': return routeDuplicates();
			case '/favicon.ico': die();

			default:
				http_response_code(404);
				exit('404 Not found');
		}
	default: exit("What?\n");
}

function routeCli() {
	echo "Building cache\n";
	buildEmptyUrlCache();

	putenv('PHP_CLI_SERVER_WORKERS=4');
	passthru(escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:3602 ' . __FILE__);
}

function routeWebHentagProxy() {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://hentag.com/api/v1/search/vault/title/");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($_POST));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	header('Content-Type: application/json');
	echo curl_exec($ch);
	curl_close($ch);
}

function routeWebFakkuProxy() {
	$query = strval(isset($_GET['query']) ? $_GET['query'] : '');

	$url = "https://www.fakku.net/suggest/" . rawurlencode($query);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		// "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0",
	    "X-Requested-With: XMLHttpRequest",
	]);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	header('Content-Type: application/json');
	echo curl_exec($ch);
	curl_close($ch);
}

function routeSm() {
	$fakkuindex = require __DIR__ . '/temp/fakkusm.php';
	$iroindex = require __DIR__ . '/temp/irodorism.php';
	$query = strval(isset($_GET['query']) ? $_GET['query'] : '');

	$ret = [];
	foreach ($fakkuindex as $url => $val) {
		if (strnatcasecmp($query, $val['title']) === 0) {
			$ret[] = [
				'url' => $url,
				'title' => "[{$fakkuindex[$url]['artist']}] {$fakkuindex[$url]['title']}",
				'source' => 'Fakku',
			];
		}
	}

	foreach ($iroindex as $url => $val) {
		if (strnatcasecmp($query, $val['title']) === 0) {
			$ret[] = [
				'url' => $url,
				'title' => "[{$iroindex[$url]['artist']}] {$iroindex[$url]['title']}",
				'source' => 'Irodori',
			];
		}
	}

	header('Content-Type: application/json');
	echo json_encode($ret);
}

function routeWebHide() {
	$key = strval(isset($_GET['key']) ? $_GET['key'] : '');
	$hideFn = __DIR__ . '/temp/hidden.json';
	if (file_exists($hideFn)) {
		$hide = json_decode(file_get_contents($hideFn), true);
	} else {
		$hide = [];
	}

	if (!empty($key)) {
		$hide[$key] = true;
	}

	file_put_contents($hideFn, json_encode($hide, JSON_PRETTY_PRINT));
	echo '<script type="text/javascript">location.replace("index.php")</script>';
}

function routeWebUpdate() {
	$key = strval(isset($_POST['key']) ? $_POST['key'] : '');
	$entries = isset($_POST['entries']) ? $_POST['entries'] : [];

	if (empty($entries) || !is_array($entries)) {
		http_response_code(400);
		exit("Empty entries");
	}

	$yamlFn = __DIR__ . "/{$key}";
	if (!file_exists($yamlFn)) {
		http_response_code(400);
		exit("File {$yamlFn} not found");
	}
	$spec = Spec::fromFile($yamlFn);

	foreach ($entries as $entry) {
		if (!in_array($entry['source'], ValNorm::$urlSources)) {
			http_response_code(400);
			exit("Unknown source " . h($entry['source']));
		}

		if (empty($spec->URLSource)) {
			$spec->URLSource = $entry['source'];
		}

		$spec->URL[$entry['source']] = $entry['url'];
	}


	$spec->save();

	echo '<script type="text/javascript">location.replace("index.php")</script>';
}

function routeWebIndex() {
	$hideFn = __DIR__ . '/temp/hidden.json';
	if (file_exists($hideFn)) {
		$hide = json_decode(file_get_contents($hideFn), true);
	} else {
		$hide = [];
	}

	$missing = getEmptyUrlsCache();
	?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>URL updater</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
		<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
	</head>

	<body>
		<div class="container">
			<?php $count = 0; ?>
			<div class="alert alert-info mt-2">
				Count: <?= count($missing) ?>
			</div>
			<div class="card mt-4">
				<div class="card-body">
					<a href="duplicates.php" target="_blank" class="btn btn-primary">Duplicates</a>
				</div>
			</div>

			<?php foreach ($missing as $baseName => $spec): ?>
				<?php
				if ($count > 20) {
					break;
				}
				$count++;
				$query = [];

				$pages = intval($spec->Pages ?? 0);
				$artist = '';
				if (!empty($spec->Artist)) {
					$artist = implode(' ', $spec->Artist);
					$query[] = $artist;
				}

				$publisher = '';
				if (!empty($spec->Publisher)) {
					$publisher = implode(' ', $spec->Publisher);
				}

				$title = '';
				if (empty($title)) {
					$title = $spec->Title;
					$query[] = $title;
				}

				$query = implode(' ', $query);

				$hentagUrl = "https://hentag.com/?" . http_build_query([
					't' => $query,
				]);

				$googleUrl = "https://www.google.com/search?" . http_build_query([
					'q' => "{$artist} \"{$title}\"",
				]);

				$fakkuUrl = "https://www.fakku.net/search/" . rawurlencode($query);

				$irodoriUrl = "https://irodoricomics.com/index.php?" . http_build_query([
					'route' => 'product/search',
					'search' => str_replace(['-', ':'], ' ', $title),
				]);

				$irodoriApiUrl = "https://irodoricomics.com/index.php?" . http_build_query([
					'route' => 'extension/module/me_ajax_search/search',
					'search' => str_replace(['-', ':'], ' ', $title),
				]);

				$fakkuApiQuery = str_replace(['-', ':'], ' ', $title);

				$_2dmarketUrl = "https://2d-market.com/Search?" . http_build_query([
					'search_value' => $title,
					'type' => 'all',
				]);

				?>
				<div class="card mt-2">
					<div class="card-header">
						<h4>
							<?php if (!empty($artist)): ?>
								<span class="text-success">[<?= h($artist) ?>]</span>
							<?php endif; ?>
							<?= h($title) ?>
							<?php if (!empty($publisher)): ?>
								<span class="text-muted">(<?= h($publisher) ?>)</span>
							<?php endif; ?>
							<?php if ($pages > 0): ?>
								(<?= $pages == 1 ? "1 page" : "{$pages} pages" ?>)
							<?php endif; ?>
						</h4>
					</div>
					<div style="display: none" class="card-header title_compare"></div>
					<div class="card-body">
						<p class="px-2"><code><?= h($baseName) ?></code></p>
						<form method="post" action="update.php">
							<input type="hidden" name="key" value="<?= $baseName ?>" />
							<div class="list-target">
								<template>
									<fieldset class="p-1 d-flex">
										<input type="text" class="form-control mx-1" placeholder="URL" name="url" />
										<select class="form-select mx-1" name="source">
											<option value="" selected>(Select)</option>
											<?php foreach (ValNorm::$urlSources as $source): ?>
											<option value="<?= h($source) ?>"><?= h($source) ?></option>
											<?php endforeach; ?>
										</select>
										<button type="button" class="btn btn-danger mx-1 option-remove">x</button>
									</fieldset>
								</template>
							</div>
							<div class="p-1">
								<button class="btn btn-primary" type="submit" name="op" value="updateUrl">Save</button>
								<a href="hide.php?<?= http_build_query(['key' => $baseName]) ?>" class="btn btn-warning">Hide</a>
								<button class="btn btn-success add-empty-option" type="button">+</button>
								<span class="comment ms-2 text-danger"></span>
							</div>
						</form>
					</div>
					<div class="card-footer">
						<button type="button" class="sm-api btn btn-primary" data-query="<?= h($title) ?>">SM</button>
						<button type="button" class="hentag-api btn btn-primary" data-query="<?= h($query) ?>">Hentag API</button>
						<a target="hentag" rel="noopener,noreferrer" href="<?= h($hentagUrl) ?>" class="btn btn-primary">Hentag</a>
						<a target="google" rel="noopener,noreferrer" href="<?= h($googleUrl) ?>" class="btn btn-primary">Google</a>
						<a target="fakku" rel="noopener,noreferrer" href="<?= h($fakkuUrl) ?>" class="btn btn-primary">Fakku</a>
						<a target="irodori" rel="noopener,noreferrer" href="<?= h($irodoriUrl) ?>" class="btn btn-primary">Irodori</a>
						<a target="2dmarket" rel="noopener,noreferrer" href="<?= h($_2dmarketUrl) ?>" class="btn btn-primary">2D Market</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<script type="text/javascript">
		var $body = $('body');
		var i = 0;

		function addOption($target, url, source) {
			i++;
			var templateHtml = $target.find('template').html();
			var $item = $(templateHtml);

			var $url = $item.find('[name=url]');
			$url.attr('name', 'entries[' + i + '][url]');
			$url.val(url);

			var $source = $item.find('[name=source]');
			$source.attr('name', 'entries[' + i + '][source]');
			$source.val(source);

			$target.append($item);

			$url.focus().select();
		}

		$('body').on('click', '.add-empty-option', function (e) {
			var $target = $(e.currentTarget).closest('div.card').find('.list-target');
			addOption($target);
		});

		$('body').on('click', '.option-remove', function (e) {
			$(e.currentTarget).closest('fieldset').remove();
		});

		$body.on('click', 'button.sm-api', function (e) {
			e.preventDefault();
			var $current = $(e.currentTarget);
			var query = $current.data('query');
			var $listTarget = $current.closest('div.card').find('.list-target');
			$current.attr('disabled', true);

			$.ajax({
				type: 'GET',
				url: 'sm.php',
				data: {query: query},
				success: function(data) {
					$current.removeAttr('disabled');

					if (data.length) {
						for (const result of data) {
							addOption($listTarget, result.url, result.source);
						}
					}
				},
				error: function () {
					$current.removeAttr('disabled');
				}
			});
		});

		$body.on('click', 'button.hentag-api', function (e) {
			e.preventDefault();
			var $current = $(e.currentTarget);
			var query = $current.data('query');
			var $listTarget = $current.closest('div.card').find('.list-target');
			var $comment = $current.closest('div.card').find('span.comment');
			var $titleCompare = $current.closest('div.card').find('div.title_compare');
			$current.attr('disabled', true);

			$.ajax({
				type: 'POST',
				url: 'hentagProxy.php',
				data: {title: query},
				success: function(data) {
					$current.removeAttr('disabled');

					if ('length' in data) {
						for (const result of data) {
							if ('locations' in result) {
								if (result.otherTags) {
									var evilTags = result.otherTags.filter(v => (v == 'forced' || v == 'incest' || v == 'loli' || v == 'lolicon' || v == 'shotacon'));
									if (evilTags.length > 0) {
										$comment.html('Contains hidden tags (' + evilTags.join(', ') + ')');
									}
								}

								for (const location of result.locations) {
									if (location.includes('fakku.net')) {
										addOption($listTarget, location, 'Fakku');
									}
									if (location.includes('irodoricomics.com')) {
										addOption($listTarget, location, 'Irodori');
									}
									if (location.includes('doujin.io')) {
										addOption($listTarget, location, 'J18');
									}
								}
							}
						}
					}
				},
				error: function () {
					$current.removeAttr('disabled');
				}
			});
		})

		$body.on('click', 'div.card-footer a', function (e) {
			var $current = $(e.currentTarget);
			var $url = $current.closest('div.card').find('input[name="url"]');
			$url.focus();
		});

		$body.on('keyup', 'input[name="url"]', function (e) {
			var $current = $(e.currentTarget);
			var $select = $current.closest('div.card').find('select[name="source"]');
			if ($select.val() !== "") {
				return;
			}

			if ($current.val().indexOf('fakku.net') !== -1) {
				$select.val('Fakku');
			}

			if ($current.val().indexOf('irodoricomics.com') !== -1) {
				$current.val($current.val().split('?')[0]);
				$select.val('Irodori');
			}

			if ($current.val().indexOf('doujin.io') !== -1) {
				$select.val('J18');
			}

			if ($current.val().indexOf('projecthentai.com') !== -1) {
				$select.val('ProjectHentai');
			}
		})
		</script>
	</body>
	</html>
	<?php
}

function routeDuplicates() {
	header('Content-Type: text/plain');
	$groups = [];
	foreach (streamSpecs() as $spec) {
		if (!empty($spec->URL)) {
			foreach ($spec->URL as $url) {
				$groups[$url][] = $spec->getBaseName();
			}
		}
	}

	foreach ($groups as $url => $names) {
		$names = array_unique($names);
		sort($names);
		if (count($names) > 1) {
			foreach ($names as $name) {
				// $spec = Spec::fromfile($name);
				// if (!empty($spec->Filesize)) {
				// 	echo '#' . round($spec->Filesize/1024/1024, 1) . 'MB', PHP_EOL;
				// }
				echo 'rm ' . escapeshellarg($name), PHP_EOL;
			}
			echo PHP_EOL;
		}
	}
}

function buildEmptyUrlCache() {
	$cacheFn = __DIR__ . '/temp/emptyUrlCache.json';

	$files = listFiles();

	$i = 0;
	$count = count($files);

	$missing = [];
	foreach ($files as $yamlFn) {
		$i++;
		if ($i % 1000 === 0) {
			echo "{$i}/{$count}\n";
		}
		$rFile = relativeDir($yamlFn);

		$yaml = file_get_contents($yamlFn);
		$meta = yaml_parse($yaml);
		if (empty($meta)) {
			continue;
		}

		if (!empty($meta['URL'])) {
			continue;
		}

		$missing[] = $rFile;
	}

	file_put_contents($cacheFn, json_encode($missing, JSON_PRETTY_PRINT));
}

function getEmptyUrlsCache() {
	$cacheFn = __DIR__ . '/temp/emptyUrlCache.json';
	$files = json_decode(file_get_contents($cacheFn), true);

	$hideFn = __DIR__ . '/temp/hidden.json';
	if (file_exists($hideFn)) {
		$hide = json_decode(file_get_contents($hideFn), true);
	} else {
		$hide = [];
	}

	$missing = [];
	foreach ($files as $yamlFn) {
		$rFile = relativeDir($yamlFn);
		if (!empty($hide[$rFile])) {
			continue;
		}
		$fn = baseDir() . '/' . $yamlFn;
		$spec = Spec::fromFile($fn);

		if (!empty($spec->URL)) {
			continue;
		}

		$missing[$rFile] = $spec;
	}
	return $missing;
}
