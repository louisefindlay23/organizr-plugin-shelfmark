/* SHELFMARK JS FILE */
var shelfmarkTimer = null;
var shelfmarkMode = null; // 'combined' (Downloader tab) or 'panel'
var shelfmarkCounts = {queue: 0, requests: 0};
var shelfmarkShowType = true;
$('body').arrive('#homepage-items', {existing: true}, function () {
	shelfmarkLaunch();
});
function shelfmarkEscape(value) {
	return $('<div>').text(value === null || value === undefined ? '' : value).html();
}
function shelfmarkBar(pct, state) {
	return `<div class="progress progress-lg m-b-0"><div class="progress-bar progress-bar-${state}" style="width:${pct}%" role="progressbar">${pct}%</div></div>`;
}
function shelfmarkQueueRow(item) {
	var pct = Math.max(0, Math.min(100, Math.floor(Number(item.progress) || 0)));
	var state = item.status === 'error' ? 'danger' : (item.status === 'complete' ? 'success' : 'info');
	return `<tr>
		<td class="max-texts">${shelfmarkEscape(item.title)}</td>
		<td class="hidden-xs">${shelfmarkEscape(item.status_message || item.status)}</td>
		<td class="hidden-xs">${shelfmarkEscape(item.size)}</td>
		<td class="hidden-xs"><span class="label label-info">${shelfmarkEscape(item.source_display_name)}</span></td>
		<td class="text-right">${shelfmarkBar(pct, state)}</td>
	</tr>`;
}
function shelfmarkRequestRow(item) {
	var state = item.status === 'rejected' ? 'danger' : (item.status === 'pending' ? 'warning' : 'success');
	return `<tr>
		<td class="max-texts">${shelfmarkEscape(item.title)}</td>
		<td class="hidden-xs">${shelfmarkEscape(item.author)}</td>
		<td><span class="label label-${state}">${shelfmarkEscape(item.status)}</span></td>
		${shelfmarkShowType ? `<td class="hidden-xs text-right">${shelfmarkEscape(item.content_type)}</td>` : ''}
	</tr>`;
}
// Same Queue/History sub-tab markup NZBGet uses; ours are Downloads and Requests.
function shelfmarkTabs() {
	return `<ul class="nav customtab nav-tabs m-t-5" role="tablist">
		<li role="presentation" class="active"><a href="#shelfmark-queue" role="tab" data-toggle="tab" aria-expanded="true"><span class="visible-xs"><i class="ti-download"></i></span><span class="hidden-xs" lang="en">DOWNLOADS</span></a></li>
		<li role="presentation"><a href="#shelfmark-requests" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-bookmark"></i></span><span class="hidden-xs" lang="en">REQUESTS</span></a></li>
	</ul><div class="clearfix"></div>
	<div class="tab-content m-t-0 listingSingle">
		<div role="tabpanel" class="tab-pane fade active in" id="shelfmark-queue"><div class="inbox-center table-responsive"><table class="table table-hover"><tbody class="shelfmark-queue"></tbody></table></div><div class="clearfix"></div></div>
		<div role="tabpanel" class="tab-pane fade" id="shelfmark-requests"><div class="inbox-center table-responsive"><table class="table table-hover"><tbody class="shelfmark-requests"></tbody></table></div><div class="clearfix"></div></div>
	</div>`;
}
function shelfmarkMount(data) {
	if (data.combine && $('#homepageOrderdownloader').length) {
		buildDownloaderCombined('shelfmark');
		$('#combined-shelfmark').html(shelfmarkTabs());
		$('.combinedMenuList a[href="#combined-shelfmark"] img').attr('src', data.logo);
		shelfmarkMode = 'combined';
		// The tab's click handler calls homepageDownloader(source), which has no Shelfmark route in core.
		if (!window.shelfmarkOrigDownloader) {
			window.shelfmarkOrigDownloader = window.homepageDownloader;
			window.homepageDownloader = function (type) {
				return type === 'shelfmark' ? shelfmarkRefresh() : window.shelfmarkOrigDownloader.apply(this, arguments);
			};
		}
	} else {
		$('#homepage-items').prepend(`<div id="shelfmarkQueueContainer" class="homepage-item"><div class="white-box"><h2 class="m-t-0">${shelfmarkEscape(data.title || 'Shelfmark')}</h2>${shelfmarkTabs()}</div></div>`);
		shelfmarkMode = 'panel';
	}
}
function shelfmarkFill(selector, items, rowFn, message) {
	$(selector).html(items.length ? items.map(rowFn).join('') : `<tr><td class="max-texts" lang="en">${message}</td></tr>`);
}
function shelfmarkFail(selector, xhr) {
	var message = (xhr.responseJSON && xhr.responseJSON.response && xhr.responseJSON.response.message) || 'Could not reach Shelfmark';
	$(selector).html(`<tr><td class="max-texts">${shelfmarkEscape(message)}</td></tr>`);
}
// The badge counts active downloads, or pending requests when there are none. Like the other
// downloaders it leaves out anything finished (complete, cancelled, rejected).
function shelfmarkCount() {
	$('#count-shelfmark').html(shelfmarkCounts.queue || shelfmarkCounts.requests);
}
function shelfmarkRefreshRequests() {
	$.getJSON('api/v2/plugins/shelfmark/requests').done(function (res) {
		var data = res.response.data || {};
		var items = data.items || [];
		shelfmarkShowType = data.show_type !== false;
		shelfmarkFill('.shelfmark-requests', items, shelfmarkRequestRow, 'No requests');
		shelfmarkCounts.requests = items.filter(function (i) { return i.status === 'pending'; }).length;
		shelfmarkCount();
	}).fail(function (xhr) {
		shelfmarkFail('.shelfmark-requests', xhr);
	});
}
// Queue first: its response says whether to mount in the Downloader or as a panel. Requests follow once mounted.
function shelfmarkRefresh() {
	$.getJSON('api/v2/plugins/shelfmark/queue').done(function (res) {
		var data = res.response.data || {};
		if (shelfmarkMode === null) {
			shelfmarkMount(data);
		}
		var items = data.items || [];
		shelfmarkFill('.shelfmark-queue', items, shelfmarkQueueRow, 'Nothing in queue');
		shelfmarkCounts.queue = items.filter(function (i) { return i.status !== 'complete' && i.status !== 'cancelled'; }).length;
		shelfmarkCount();
	}).fail(function (xhr) {
		if (shelfmarkMode === null) {
			shelfmarkMount({});
		}
		shelfmarkFail('.shelfmark-queue', xhr);
	}).always(shelfmarkRefreshRequests);
}
function shelfmarkLaunch() {
	clearInterval(shelfmarkTimer);
	shelfmarkMode = null;
	var plugins = activeInfo.plugins;
	if (plugins['SHELFMARK-enabled'] !== true || activeInfo.user.groupID > plugins.includes['SHELFMARK-Auth-include']) {
		return;
	}
	shelfmarkRefresh();
	shelfmarkTimer = setInterval(function () {
		if (!$('#homepage-items').length) { // left the homepage
			clearInterval(shelfmarkTimer);
			return;
		}
		shelfmarkRefresh();
	}, 10000); // poll every 10s
}
