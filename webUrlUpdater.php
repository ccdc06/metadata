<?php
require __DIR__ . '/functions.php';

switch (php_sapi_name()) {
	case 'cli': return routeCli();
	case 'cli-server':
		switch ($_SERVER['SCRIPT_NAME']) {
			case '/': case '/index.php': return routeWebIndex();
			case '/update.php': return routeWebUpdate();
			case '/hide.php': return routeWebHide();
			case '/hentagProxy.php': return routeWebHentagProxy();

			default:
				http_response_code(404);
				exit('404 Not found');
		}
	default: exit("What?\n");
}

function routeCli() {
	// putenv('PHP_CLI_SERVER_WORKERS=4');
	passthru(escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:8000 ' . __FILE__);
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
	$url = strval(isset($_POST['url']) ? $_POST['url'] : '');
	$key = strval(isset($_POST['key']) ? $_POST['key'] : '');
	$source = strval(isset($_POST['source']) ? $_POST['source'] : '');

	if (empty($url)) {
		http_response_code(400);
		exit("Empty url");
	}

	if (empty($source)) {
		http_response_code(400);
		exit("Empty source");
	}

	if (!in_array($source, Lists::$urlSources)) {
		http_response_code(400);
		exit("Unknown source " . h($source));
	}

	if (empty($key)) {
		http_response_code(400);
		exit("Empty key");
	}

	$yamlFn = __DIR__ . "/{$key}";
	if (!file_exists($yamlFn)) {
		http_response_code(400);
		exit("File {$yamlFn} not found");
	}

	$meta = yaml_parse(file_get_contents($yamlFn));
	if (empty($meta)) {
		exit("Bad YAML file");
	}

	$meta['URL'][$source] = $url;

	reorderFields($meta);
	file_put_contents($yamlFn, yaml_emit($meta));

	echo '<script type="text/javascript">location.replace("index.php")</script>';
}

function routeWebIndex() {
	$hideFn = __DIR__ . '/temp/hidden.json';
	if (file_exists($hideFn)) {
		$hide = json_decode(file_get_contents($hideFn), true);
	} else {
		$hide = [];
	}

	$files = listFiles();
	$limit = intval(isset($_GET['limit']) ? $_GET['limit'] : 10);
	if ($limit <= 0) {
		$limit = 10;
	}

	$missing = [];
	foreach ($files as $yamlFn) {
		$rFile = relativeDir($yamlFn);
		if (!empty($hide[$rFile])) {
			continue;
		}

		$yaml = file_get_contents($yamlFn);
		$meta = yaml_parse($yaml);
		if (empty($meta)) {
			continue;
		}

		if (!empty($meta['URL'])) {
			continue;
		}

		$missing[$rFile] = $meta;

		if (count($missing) >= $limit) {
			break;
		}
	}
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
			<?php foreach ($missing as $key => $val): ?>
				<?php
				$query = [];

				$pages = intval($val['Pages'] ?? 0);
				$artist = '';
				if (!empty($val['Artist'])) {
					$artist = implode(' ', $val['Artist']);
					$query[] = $artist;
				}

				$publisher = '';
				if (!empty($val['Publisher'])) {
					$publisher = implode(' ', $val['Publisher']);
				}

				$title = '';
				if (empty($title)) {
					$title = $val['Title'];
					$query[] = $title;
				}

				$query = implode(' ', $query);

				$hentagUrl = "https://hentag.com/?" . http_build_query([
					't' => $query,
				]);

				$googleUrl = "https://www.google.com/search?" . http_build_query([
					'q' => $query,
				]);

				$fakkuUrl = "https://www.fakku.net/search/" . rawurlencode($query);

				$irodoriUrl = "https://irodoricomics.com/index.php?" . http_build_query([
					'route' => 'product/search',
					'search' => $title,
				]);

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
					<div class="card-body">
						<p class="px-2"><code><?= h($key) ?></code></p>
						<form method="post" action="update.php">
							<input type="hidden" name="key" value="<?= $key ?>" />
							<div class="p-1 d-flex">
								<input type="text" class="form-control mx-1" placeholder="URL" name="url" />
								<select class="form-select" name="source">
									<option value="" selected>(Select)</option>
									<?php foreach (Lists::$urlSources as $source): ?>
										<option value="<?= h($source) ?>"><?= h($source) ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class=" p-1">
								<button class="btn btn-primary" type="submit" name="op" value="updateUrl">Save</button>
								<a href="hide.php?<?= http_build_query(['key' => $key]) ?>" class="btn btn-warning">Hide</a>
							</div>
						</form>
					</div>
					<div class="card-footer">
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

		$body.on('click', 'button.hentag-api', function (e) {
			e.preventDefault();
			var $current = $(e.currentTarget);
			var $url = $current.closest('div.card').find('input[name="url"]');
			var $select = $current.closest('div.card').find('select[name="source"]');
			var query = $current.data('query');
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
								var search = result.locations.filter(v => v.includes('fakku.net'));
								if ('0' in search) {
									window.open(search[0], 'fakku').focus();
									$url.val(search[0]).focus().select();
									$select.val('Fakku');
									break;
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
				$select.val('Irodori');
			}
		})
		</script>
	</body>
	</html>
	<?php
}
