<?php
// Where this folder is served from: api/plugins/ when bundled, data/plugins/ when installed from a marketplace.
$shelfmarkWebDir = (strpos(__DIR__, DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR) !== false ? 'data/plugins/' : 'api/plugins/') . 'shelfmark/';
// PLUGIN INFORMATION
$GLOBALS['plugins']['Shelfmark'] = array(
	'name' => 'Shelfmark',
	'author' => 'Louise Findlay',
	'category' => 'Downloaders',
	'link' => 'https://github.com/calibrain/shelfmark',
	'license' => 'personal,business',
	'idPrefix' => 'SHELFMARK',
	'configPrefix' => 'SHELFMARK',
	'version' => '1.0.0',
	'image' => $shelfmarkWebDir . 'logo.png',
	'settings' => true,
	'bind' => true,
	'api' => 'api/v2/plugins/shelfmark/settings',
	'homepage' => false // the plugin's JS adds its own panel to the homepage
);

class Shelfmark extends Organizr
{
	public function shelfmarkGetSettings()
	{
		return array(
			'Shelfmark' => array(
				array(
					'type' => 'select',
					'name' => 'SHELFMARK-Auth-include',
					'label' => 'Minimum Authentication',
					'value' => $this->config['SHELFMARK-Auth-include'],
					'options' => $this->groupSelect()
				),
				array(
					'type' => 'input',
					'name' => 'SHELFMARK-url',
					'label' => 'Shelfmark URL',
					'placeholder' => 'ex: http://192.168.1.80:8086',
					'value' => $this->config['SHELFMARK-url']
				),
				array(
					'type' => 'password-alt',
					'name' => 'SHELFMARK-apikey',
					'label' => 'Admin API Key',
					'help' => 'Shelfmark\'s SHELFMARK_API_KEY. It is an admin key, so treat it like a password. The plugin only sends GET requests (/api/activity/snapshot) and keeps the key server-side.',
					'value' => $this->config['SHELFMARK-apikey']
				),
				array(
					'type' => 'input',
					'name' => 'SHELFMARK-title',
					'label' => 'Homepage Title',
					'value' => $this->config['SHELFMARK-title']
				),
				array(
					'type' => 'switch',
					'name' => 'SHELFMARK-combine',
					'label' => 'Combine into Downloader',
					'help' => 'Show Shelfmark as a tab in the homepage Downloader item instead of its own panel. Needs the Downloader homepage item enabled.',
					'value' => $this->config['SHELFMARK-combine']
				),
				array(
					'type' => 'switch',
					'name' => 'SHELFMARK-showType',
					'label' => 'Show request type',
					'help' => 'Show the ebook/audiobook column in the Requests tab.',
					'value' => $this->config['SHELFMARK-showType']
				)
			)
		);
	}

	// GETs a Shelfmark admin endpoint server-side so the key never reaches the browser.
	private function shelfmarkGet($path)
	{
		$url = rtrim($this->config['SHELFMARK-url'], '/');
		if (!$this->config['SHELFMARK-enabled'] || $url === '' || $this->config['SHELFMARK-apikey'] === '') {
			$this->setAPIResponse('error', 'Shelfmark is not configured', 409);
			return null;
		}
		try {
			$response = Requests::get($url . $path, array('X-Api-Key' => $this->config['SHELFMARK-apikey']), array('timeout' => 5));
		} catch (Requests_Exception $e) {
			$this->setAPIResponse('error', 'Could not reach Shelfmark', 502);
			return null;
		}
		$data = $response->success ? json_decode($response->body, true) : null;
		if (!is_array($data)) {
			$this->setAPIResponse('error', 'Shelfmark returned HTTP ' . $response->status_code, 502);
			return null;
		}
		return $data;
	}

	// The admin key sees far more than a dashboard needs: keep display fields only, and strip links
	// from status messages because a client error can quote an indexer key or tracker passkey.
	public function shelfmarkQueue()
	{
		$snapshot = $this->shelfmarkGet('/api/activity/snapshot');
		if ($snapshot === null) {
			return false;
		}
		$items = array();
		foreach ((array)($snapshot['status'] ?? array()) as $bucket => $tasks) {
			foreach ((array)$tasks as $task) {
				// Finished downloads come from history without a status or progress of their own.
				$items[] = array(
					'title' => $task['title'] ?? '',
					'status' => $task['status'] ?? $bucket,
					'status_message' => preg_replace('~\S*://\S+~', '[link]', (string)($task['status_message'] ?? '')),
					'size' => $task['size'] ?? '',
					'source_display_name' => $task['source_display_name'] ?? '',
					'progress' => $task['progress'] ?? ($bucket === 'complete' ? 100 : 0),
					'added_time' => $task['added_time'] ?? 0
				);
			}
		}
		usort($items, function ($a, $b) {
			return $b['added_time'] <=> $a['added_time'];
		});
		$this->setAPIResponse('success', null, 200, array('items' => $items, 'title' => $this->config['SHELFMARK-title'], 'combine' => (bool)$this->config['SHELFMARK-combine'], 'logo' => $GLOBALS['plugins']['Shelfmark']['image']));
		return true;
	}

	public function shelfmarkRequests()
	{
		$snapshot = $this->shelfmarkGet('/api/activity/snapshot');
		if ($snapshot === null) {
			return false;
		}
		$items = array();
		foreach ((array)($snapshot['requests'] ?? array()) as $row) {
			$book = $row['book_data'] ?? array();
			$items[] = array(
				'title' => $book['title'] ?? '',
				'author' => $book['author'] ?? '',
				'status' => $row['status'] ?? '',
				'content_type' => $row['content_type'] ?? ''
			);
		}
		$this->setAPIResponse('success', null, 200, array('items' => $items, 'show_type' => (bool)$this->config['SHELFMARK-showType']));
		return true;
	}
}
