/**
 * Accordion-WP Pro: Tabs and Horizontal layouts.
 *
 * The "__premium_only" part of the file name makes Freemius remove this file from the free version.
 * The free script looks layouts up in window.TCAccordionLayouts and asks them how to behave.
 */
(function ($) {
	'use strict';

	window.TCAccordionLayouts = window.TCAccordionLayouts || {};

	// Items hidden by search or by group filter buttons.
	var HIDDEN = '.tc-acc-filtered-out, .tc-acc-hidden';

	var query = window.matchMedia ? window.matchMedia('(min-width: 768px)') : null;

	// Tabs and Horizontal only apply on screens 768px and wider.
	function desktop() {
		return query ? query.matches : true;
	}

	function isOpen($li) {
		return $li.children('.responsive-accordion-head').hasClass('active');
	}

	// Left / Right / Home / End move focus between headers. Returns -1 for any other key.
	function nextSideways(key, current, count) {
		var next = -1;

		if (key === 'ArrowRight') next = current + 1;
		if (key === 'ArrowLeft') next = current - 1;
		if (key === 'Home') next = 0;
		if (key === 'End') next = count - 1;

		if (next === -1 && key !== 'ArrowLeft') {
			return -1;
		}
		if (next < 0) next = count - 1;
		if (next >= count) next = 0;

		return next;
	}

	// ---- Tabs: one panel is always open, the titles act as a tab row ----------
	window.TCAccordionLayouts.tabs = function (ctx) {
		return {
			active: desktop,
			forceCloseOthers: true,
			alwaysOneOpen: true,

			togglePanel: function ($panel, open) {
				if (open) {
					$panel.fadeIn(Math.min(ctx.animSpeed, 200));
				} else {
					$panel.hide();
				}
			},

			// Exactly one open tab among the visible items.
			sync: function () {
				var $visible = ctx.$items.not(HIDDEN);
				var $open = $visible.filter(function () {
					return isOpen($(this));
				});

				if ($open.length === 0) {
					if ($visible.length) {
						ctx.setOpen($visible.first(), true, true);
					}
				} else if ($open.length > 1) {
					$open.slice(1).each(function () {
						ctx.setOpen($(this), false, true);
					});
				}
			},

			// After searching, make sure a visible tab is open.
			afterFilter: function () {
				var $openVisible = ctx.$items.not(HIDDEN).filter(function () {
					return isOpen($(this));
				});

				if (!$openVisible.length) {
					ctx.$items.each(function () {
						if (isOpen($(this))) {
							ctx.setOpen($(this), false, true);
						}
					});

					var $first = ctx.$items.not(HIDDEN).first();
					if ($first.length) {
						ctx.setOpen($first, true, true);
					}
				}
			},

			nextIndex: nextSideways
		};
	};

	// ---- Horizontal: strips that grow sideways --------------------------------
	window.TCAccordionLayouts.horizontal = function (ctx) {
		return {
			active: desktop,
			forceCloseOthers: false,
			alwaysOneOpen: false,

			togglePanel: function ($panel, open, $head) {
				if (open) {
					$panel.show();
				} else {
					// Let the width transition finish before hiding the content.
					setTimeout(function () {
						if (!$head.hasClass('active')) {
							$panel.hide();
						}
					}, ctx.animSpeed);
				}
			},

			nextIndex: nextSideways
		};
	};

	// If this file loaded after the accordions started, start them again so they pick up the layout.
	$(function () {
		var started = $('.tc-accordion-wrapper').filter(function () {
			return !!$(this).data('tcaccApi');
		}).length;

		if (started) {
			$(document).trigger('tc_accordion_reinit');
		}
	});
})(jQuery);
