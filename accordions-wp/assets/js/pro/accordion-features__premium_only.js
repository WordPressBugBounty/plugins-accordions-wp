/**
 * Accordion-WP Pro: group filter buttons and analytics.
 *
 * The "__premium_only" part of the file name makes Freemius remove this file from the free version.
 * It talks to the free script through events: "tcacc_item_open" (an item was opened by a visitor)
 * and "tcacc_items_changed" (the visible items changed).
 */
(function ($) {
	'use strict';

	var data = window.tcaccProData || {};

	// ---- Group filter buttons ---------------------------------------------------
	$(document).on('click', '.tc-acc-group-btn', function (e) {
		e.preventDefault();

		var $button = $(this);
		var $wrapper = $button.closest('.tc-accordion-wrapper');
		var group = String($button.attr('data-group') || '');

		$wrapper.children('.tc-acc-groups').find('.tc-acc-group-btn')
			.removeClass('is-active')
			.attr('aria-pressed', 'false');

		$button.addClass('is-active').attr('aria-pressed', 'true');

		// Only this accordion's own items (a nested accordion keeps its own filter).
		$wrapper.children('.responsive-accordion').children('li').each(function () {
			var itemGroup = String($(this).attr('data-group') || '');
			$(this).toggleClass('tc-acc-hidden', group !== '' && itemGroup !== group);
		});

		// Let layouts that always keep one item open (Tabs) pick a visible one.
		$wrapper.trigger('tcacc_items_changed');
	});

	// ---- Analytics -------------------------------------------------------------
	var seen = {};

	function sendGoogleAnalytics(id, title) {
		if (typeof window.gtag === 'function') {
			window.gtag('event', 'accordion_open', {
				accordion_id: id,
				item_title: title.substring(0, 100)
			});
		} else if (window.dataLayer && typeof window.dataLayer.push === 'function') {
			window.dataLayer.push({
				event: 'accordion_open',
				accordion_id: id,
				item_title: title.substring(0, 100)
			});
		}
	}

	function sendCount(id, title) {
		if (!data.restUrl) {
			return;
		}

		var body = JSON.stringify({ id: parseInt(id, 10), title: title });

		if (navigator.sendBeacon) {
			var blob = new Blob([body], { type: 'application/json' });
			if (navigator.sendBeacon(data.restUrl, blob)) {
				return;
			}
		}

		if (window.fetch) {
			window.fetch(data.restUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: body,
				keepalive: true,
				credentials: 'omit'
			}).catch(function () {});
		}
	}

	$(document).on('tcacc_item_open', function (e) {
		var $item = $(e.target).closest('li');
		var $wrapper = $item.closest('.tc-accordion-wrapper');
		var mode = $wrapper.attr('data-tcacc-analytics');

		if (!mode || mode === 'off') {
			return;
		}

		var id = $wrapper.attr('data-tcacc-id');
		var title = $.trim($item.children('.responsive-accordion-head').find('.responsive-accordion-title').text());
		var key = id + '|' + title;

		// Count each item once per page view.
		if (!id || !title || seen[key]) {
			return;
		}
		seen[key] = true;

		if (mode === 'ga' || mode === 'both') {
			sendGoogleAnalytics(id, title);
		}
		if (mode === 'count' || mode === 'both') {
			sendCount(id, title);
		}
	});
})(jQuery);
