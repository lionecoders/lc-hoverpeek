jQuery(function ($) {

	let lchpPopup = null;
	let lchpHideTimer = null;
	let lchpHoverTimer = null;
	let lchpActiveXHR = null;

	window.lchpCache = window.lchpCache || {};

	const lchpSelector = 'a[data-lchp-post], a[data-lchp-url]';


	function lchpRemovePopup() {

		if (lchpPopup) {
			lchpPopup.remove();
			lchpPopup = null;
		}

		if (lchpActiveXHR) {
			lchpActiveXHR.abort();
			lchpActiveXHR = null;
		}
	}


	function lchpScheduleHide() {

		if (lchpActiveXHR) {
			lchpActiveXHR.abort();
			lchpActiveXHR = null;
		}

		lchpHideTimer = setTimeout(lchpRemovePopup, 250);
	}


	function lchpSmartPosition(popup, x, y) {

		const padding = 12;

		const popupWidth = popup.outerWidth();
		const popupHeight = popup.outerHeight();

		const winWidth = $(window).width();
		const winHeight = $(window).height();

		let left = x + 15;
		let top = y + 15;

		if (left + popupWidth + padding > winWidth) {
			left = x - popupWidth - 15;
		}

		if (left < padding) {
			left = padding;
		}

		if (top + popupHeight + padding > winHeight) {
			top = y - popupHeight - 15;
		}

		if (top < padding) {
			top = padding;
		}

		popup.css({ left, top });
	}


	function lchpShowPopup(data, x, y) {

		lchpRemovePopup();

		lchpPopup = $(`
			<div class="lchp-popup">
				<div class="lchp-content">

					${data.image ? `
					<div class="lchp-image">
						<img src="${data.image}" alt="">
					</div>` : ''}

					<div class="lchp-text">
						<h4>${data.title}</h4>
						<p>${data.excerpt}</p>
						<a href="${data.link}" class="lchp-more">
							Learn more →
						</a>
					</div>

				</div>
			</div>
		`).appendTo('body');

		lchpSmartPosition(lchpPopup, x, y);

		lchpPopup.on('mouseenter', () => clearTimeout(lchpHideTimer));
		lchpPopup.on('mouseleave', lchpScheduleHide);
	}


	/* Hover preview */

	$(document).on('mouseenter', lchpSelector, function (e) {

		clearTimeout(lchpHideTimer);

		const $link = $(this);

		const postID = $link.data('lchp-post');
		const url = $link.data('lchp-url');

		if (!postID && !url) return;

		const key = postID ? 'post-' + postID : 'url-' + url;

		const x = e.pageX;
		const y = e.pageY;

		/* Show instantly if cached */

		if (lchpCache[key]) {
			lchpShowPopup(lchpCache[key], x, y);
			return;
		}

		/* Fallback AJAX */

		lchpHoverTimer = setTimeout(function () {

			if (lchpActiveXHR) {
				lchpActiveXHR.abort();
			}

			lchpActiveXHR = $.post(
				LC_HOVERPEEK.ajax_url,
				{
					action: 'lc_hoverpeek_preview',
					post_id: postID || 0,
					url: url || '',
					nonce: LC_HOVERPEEK.nonce
				},
				function (res) {

					lchpActiveXHR = null;

					if (!res.success) return;

					lchpCache[key] = res.data;

					lchpShowPopup(res.data, x, y);

				}
			);

		}, 120);

	});


	$(document).on('mouseleave', lchpSelector, function () {

		clearTimeout(lchpHoverTimer);
		lchpScheduleHide();

	});


	$(document).on('scroll resize', lchpRemovePopup);


	/* Batch prefetch */

	function lchpBatchPrefetch() {

		const links = [];

		$('a[data-lchp-post], a[data-lchp-url]').each(function () {

			const postID = $(this).data('lchp-post');
			const url = $(this).data('lchp-url');

			if (postID) {
				links.push({ post_id: postID, url: '' });
			}

			if (url) {
				links.push({ post_id: 0, url: url });
			}

			if (links.length >= 10) {
				return false;
			}

		});

		if (!links.length) return;

		$.post(
			LC_HOVERPEEK.ajax_url,
			{
				action: 'lc_hoverpeek_batch',
				links: links,
				nonce: LC_HOVERPEEK.nonce
			},
			function (res) {

				if (!res.success) return;

				res.data.forEach(function (item) {

					const key = item.post_id
						? 'post-' + item.post_id
						: 'url-' + item.link;

					lchpCache[key] = item;

				});

			}
		);

	}


	$(window).on('load', function () {

		setTimeout(lchpBatchPrefetch, 800);

	});

});