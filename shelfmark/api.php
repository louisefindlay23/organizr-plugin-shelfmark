<?php
$app->get('/plugins/shelfmark/settings', function ($request, $response, $args) {
	$Shelfmark = new Shelfmark();
	if ($Shelfmark->checkRoute($request)) {
		if ($Shelfmark->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $Shelfmark->shelfmarkGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/shelfmark/queue', function ($request, $response, $args) {
	$Shelfmark = new Shelfmark();
	if ($Shelfmark->checkRoute($request)) {
		if ($Shelfmark->qualifyRequest($Shelfmark->config['SHELFMARK-Auth-include'], true)) {
			$Shelfmark->shelfmarkQueue();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/shelfmark/requests', function ($request, $response, $args) {
	$Shelfmark = new Shelfmark();
	if ($Shelfmark->checkRoute($request)) {
		if ($Shelfmark->qualifyRequest($Shelfmark->config['SHELFMARK-Auth-include'], true)) {
			$Shelfmark->shelfmarkRequests();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
