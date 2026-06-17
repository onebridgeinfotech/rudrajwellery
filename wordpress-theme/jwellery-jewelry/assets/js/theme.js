/**
 * Jwellery Jewelry — navigation, carousels, hero, cart toast, sticky ATC.
 */
(function () {
	'use strict';

	var cfg = window.jwelleryTheme || {};

	/* Mobile menu */
	var toggle = document.querySelector('.jwellery-nav-toggle');
	var nav = document.querySelector('.jwellery-nav');
	if (toggle && nav) {
		toggle.addEventListener('click', function () {
			var open = nav.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			document.body.classList.toggle('jwellery-menu-open', open);
		});
		document.addEventListener('click', function (e) {
			if (!nav.classList.contains('is-open')) {
				return;
			}
			if (nav.contains(e.target) || toggle.contains(e.target)) {
				return;
			}
			nav.classList.remove('is-open');
			toggle.setAttribute('aria-expanded', 'false');
			document.body.classList.remove('jwellery-menu-open');
		});
		window.addEventListener('resize', function () {
			if (window.innerWidth > 1024 && nav.classList.contains('is-open')) {
				nav.classList.remove('is-open');
				toggle.setAttribute('aria-expanded', 'false');
				document.body.classList.remove('jwellery-menu-open');
			}
		});
	}

	/* Shop & submenu — click to open dropdown (desktop + mobile) */
	document.querySelectorAll('.jwellery-menu .menu-item-has-children > a').forEach(function (link) {
		var parent = link.parentElement;
		var megaPanel = parent.querySelector('.jwellery-mega-menu');

		link.addEventListener('click', function (e) {
			if (parent.classList.contains('menu-item-has-mega') && megaPanel) {
				if (window.innerWidth <= 1024) {
					e.preventDefault();
					var megaOpen = parent.classList.toggle('mega-open');
					link.setAttribute('aria-expanded', megaOpen ? 'true' : 'false');
				}
				return;
			}

			var sub = parent.querySelector(':scope > .sub-menu');
			if (!sub) {
				return;
			}

			/* Desktop: follow Shop link; mobile/tablet: toggle submenu */
			if (window.innerWidth > 1024) {
				return;
			}

			e.preventDefault();
			document.querySelectorAll('.jwellery-menu .menu-item-has-children').forEach(function (li) {
				if (li === parent) {
					return;
				}
				li.classList.remove('submenu-open', 'mega-open');
				var a = li.querySelector(':scope > a');
				if (a) {
					a.setAttribute('aria-expanded', 'false');
				}
			});
			var open = parent.classList.toggle('submenu-open');
			link.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	});

	document.addEventListener('click', function (e) {
		if (e.target.closest('.jwellery-menu .menu-item-has-children')) {
			return;
		}
		document.querySelectorAll('.jwellery-menu .menu-item-has-children.submenu-open, .jwellery-menu .menu-item-has-mega.mega-open').forEach(function (li) {
			li.classList.remove('submenu-open', 'mega-open');
			var a = li.querySelector(':scope > a');
			if (a) {
				a.setAttribute('aria-expanded', 'false');
			}
		});
	});

	/* Search icon panel */
	var searchToggle = document.querySelector('.jwellery-search-toggle');
	var searchPanel = document.getElementById('jwellery-search-panel');
	function closeSearchPanel() {
		if (!searchPanel || !searchToggle) {
			return;
		}
		searchPanel.setAttribute('hidden', '');
		searchToggle.setAttribute('aria-expanded', 'false');
	}
	if (searchToggle && searchPanel) {
		searchToggle.addEventListener('click', function (e) {
			e.stopPropagation();
			var open = searchPanel.hasAttribute('hidden');
			if (open) {
				searchPanel.removeAttribute('hidden');
				searchToggle.setAttribute('aria-expanded', 'true');
				var input = searchPanel.querySelector('input[type="search"]');
				if (input) {
					input.focus();
				}
			} else {
				closeSearchPanel();
			}
		});
		document.addEventListener('click', function (e) {
			if (searchPanel.hasAttribute('hidden')) {
				return;
			}
			if (searchPanel.contains(e.target) || searchToggle.contains(e.target)) {
				return;
			}
			closeSearchPanel();
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				closeSearchPanel();
			}
		});
	}

	/* Mobile bottom bar — open search panel */
	document.querySelectorAll('[data-mobile-search]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!searchPanel || !searchToggle) {
				return;
			}
			searchPanel.removeAttribute('hidden');
			searchToggle.setAttribute('aria-expanded', 'true');
			window.scrollTo({ top: 0, behavior: 'smooth' });
			var input = searchPanel.querySelector('input[type="search"]');
			if (input) {
				setTimeout(function () {
					input.focus();
				}, 300);
			}
		});
	});

	/* Announcement rotation */
	var ann = document.querySelector('[data-announcement-rotate] .jwellery-announcement-text');
	var messages = cfg.announcements || [];
	if (ann && messages.length > 1) {
		var ai = 0;
		setInterval(function () {
			ai = (ai + 1) % messages.length;
			ann.textContent = messages[ai];
		}, 4500);
	}

	/* Hero slider */
	var hero = document.querySelector('[data-hero-slider]');
	if (hero) {
		var slides = hero.querySelectorAll('.jwellery-hero-slide');
		var dots = hero.querySelectorAll('.jwellery-hero-dot');
		var prevBtn = hero.querySelector('.jwellery-hero-prev');
		var nextBtn = hero.querySelector('.jwellery-hero-next');
		var hi = 0;
		var heroTimer = null;

		function showHeroSlide(index) {
			if (!slides.length) {
				return;
			}
			hi = (index + slides.length) % slides.length;
			slides.forEach(function (s, n) {
				s.classList.toggle('is-active', n === hi);
			});
			dots.forEach(function (d, n) {
				d.classList.toggle('is-active', n === hi);
			});
		}

		function startHeroAuto() {
			if (slides.length <= 1) {
				return;
			}
			stopHeroAuto();
			heroTimer = setInterval(function () {
				showHeroSlide(hi + 1);
			}, 5000);
		}

		function stopHeroAuto() {
			if (heroTimer) {
				clearInterval(heroTimer);
				heroTimer = null;
			}
		}

		dots.forEach(function (dot) {
			dot.addEventListener('click', function () {
				showHeroSlide(parseInt(dot.getAttribute('data-slide'), 10) || 0);
				startHeroAuto();
			});
		});

		if (prevBtn) {
			prevBtn.addEventListener('click', function () {
				showHeroSlide(hi - 1);
				startHeroAuto();
			});
		}
		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				showHeroSlide(hi + 1);
				startHeroAuto();
			});
		}

		hero.addEventListener('mouseenter', stopHeroAuto);
		hero.addEventListener('mouseleave', startHeroAuto);
		hero.addEventListener('focusin', stopHeroAuto);
		hero.addEventListener('focusout', startHeroAuto);

		startHeroAuto();
	}

	/* Carousels */
	document.querySelectorAll('.jwellery-carousel').forEach(function (carousel) {
		var track = carousel.querySelector('.jwellery-carousel-track');
		var prev = carousel.querySelector('.carousel-prev');
		var next = carousel.querySelector('.carousel-next');
		var section = carousel.closest('.jwellery-home-section');
		var currentEl = section ? section.querySelector('.carousel-current') : null;
		var dotsWrap = section ? section.querySelector('.carousel-dots') : null;
		var isDealsCarousel = carousel.hasAttribute('data-deals-carousel');
		var dealsMobileMq = window.matchMedia('(max-width: 1024px)');
		if (!track) {
			return;
		}

		var items = track.querySelectorAll('.product, .category-card');

		function getStep() {
			if (!items.length) {
				return 240;
			}
			var first = items[0];
			var gap = 16;
			if (typeof window.getComputedStyle !== 'undefined') {
				var grid = track.querySelector('.jwellery-product-grid');
				if (grid) {
					var g = window.getComputedStyle(grid).columnGap || window.getComputedStyle(grid).gap;
					if (g) {
						gap = parseFloat(g) || gap;
					}
				}
			}
			return first.offsetWidth + gap;
		}

		function scrollToIndex(index) {
			var step = getStep();
			var left = Math.max(0, index * step);
			track.scrollTo({ left: left, behavior: 'smooth' });
			updateCounter(index);
		}

		function updateCounter(index) {
			if (currentEl) {
				currentEl.textContent = String((index || 0) + 1);
			}
			if (dotsWrap) {
				dotsWrap.querySelectorAll('.carousel-dot').forEach(function (dot, n) {
					dot.classList.toggle('is-active', n === (index || 0));
				});
			}
		}

		if (prev) {
			prev.addEventListener('click', function () {
				var step = getStep();
				var idx = Math.round(track.scrollLeft / step) - 1;
				scrollToIndex(Math.max(0, idx));
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				var step = getStep();
				var idx = Math.round(track.scrollLeft / step) + 1;
				scrollToIndex(Math.min(items.length - 1, idx));
			});
		}

		if (dotsWrap) {
			dotsWrap.querySelectorAll('.carousel-dot').forEach(function (dot) {
				dot.addEventListener('click', function () {
					scrollToIndex(parseInt(dot.getAttribute('data-index'), 10) || 0);
				});
			});
		}

		track.addEventListener('scroll', function () {
			var step = getStep();
			updateCounter(Math.round(track.scrollLeft / step));
		});

		var autoTimer = null;
		function startAuto() {
			if (autoTimer || items.length < 2) {
				return;
			}
			var autoIndex = 0;
			autoTimer = setInterval(function () {
				if (isDealsCarousel && !dealsMobileMq.matches) {
					return;
				}
				autoIndex = (autoIndex + 1) % items.length;
				scrollToIndex(autoIndex);
			}, isDealsCarousel ? 4000 : 5000);
		}

		function stopAuto() {
			if (!autoTimer) {
				return;
			}
			clearInterval(autoTimer);
			autoTimer = null;
		}

		if (cfg.carouselAuto && items.length > 1) {
			startAuto();
			carousel.addEventListener('mouseenter', stopAuto);
			carousel.addEventListener('mouseleave', startAuto);
			carousel.addEventListener('touchstart', stopAuto, { passive: true });
			carousel.addEventListener('touchend', function () {
				setTimeout(startAuto, 3000);
			}, { passive: true });
			if (isDealsCarousel && dealsMobileMq.addEventListener) {
				dealsMobileMq.addEventListener('change', function () {
					stopAuto();
					startAuto();
				});
			}
		}
	});

	/* Hot Deals — auto-scroll on mobile/tablet (carousel + cached static fallback) */
	function initDealsMobileStrip() {
		var mq = window.matchMedia('(max-width: 1024px)');
		var strips = [];

		document.querySelectorAll('.jwellery-home-section--steal-deals').forEach(function (section) {
			var carousel = section.querySelector('.jwellery-carousel--deals');
			var track = carousel
				? carousel.querySelector('.jwellery-carousel-track')
				: section.querySelector('.jwellery-product-grid--deals');
			if (!track) {
				return;
			}
			var items = track.querySelectorAll('.product');
			if (items.length < 2) {
				return;
			}
			strips.push({ track: track, items: items, index: 0, timer: null });
		});

		function getStep(track, items) {
			if (!items.length) {
				return 280;
			}
			var gap = 14;
			var grid = track.querySelector('.jwellery-product-grid--deals') || track;
			if (typeof window.getComputedStyle !== 'undefined') {
				var g = window.getComputedStyle(grid).columnGap || window.getComputedStyle(grid).gap;
				if (g) {
					gap = parseFloat(g) || gap;
				}
			}
			return items[0].offsetWidth + gap;
		}

		function scrollStrip(strip, index) {
			var step = getStep(strip.track, strip.items);
			strip.track.scrollTo({ left: Math.max(0, index * step), behavior: 'smooth' });
			strip.index = index;
			var section = strip.track.closest('.jwellery-home-section--steal-deals');
			var currentEl = section ? section.querySelector('.carousel-current') : null;
			var dotsWrap = section ? section.querySelector('.carousel-dots') : null;
			if (currentEl) {
				currentEl.textContent = String(index + 1);
			}
			if (dotsWrap) {
				dotsWrap.querySelectorAll('.carousel-dot').forEach(function (dot, n) {
					dot.classList.toggle('is-active', n === index);
				});
			}
		}

		function stopAll() {
			strips.forEach(function (strip) {
				if (strip.timer) {
					clearInterval(strip.timer);
					strip.timer = null;
				}
			});
		}

		function startAll() {
			if (!mq.matches || !cfg.carouselAuto) {
				stopAll();
				return;
			}
			strips.forEach(function (strip) {
				if (strip.timer || strip.items.length < 2) {
					return;
				}
				strip.timer = setInterval(function () {
					if (!mq.matches) {
						return;
					}
					var next = (strip.index + 1) % strip.items.length;
					scrollStrip(strip, next);
				}, 4000);
			});
		}

		strips.forEach(function (strip) {
			strip.track.addEventListener('scroll', function () {
				var step = getStep(strip.track, strip.items);
				strip.index = Math.round(strip.track.scrollLeft / step);
			});
			strip.track.addEventListener('touchstart', stopAll, { passive: true });
			strip.track.addEventListener('touchend', function () {
				setTimeout(startAll, 3500);
			}, { passive: true });
		});

		if (mq.addEventListener) {
			mq.addEventListener('change', function () {
				stopAll();
				startAll();
			});
		}

		startAll();
	}

	initDealsMobileStrip();

	/* Add to cart toast + loading */
	var toast = document.getElementById('jwellery-toast');
	function showToast(msg) {
		if (!toast) {
			return;
		}
		toast.textContent = msg;
		toast.removeAttribute('hidden');
		toast.classList.add('is-visible');
		setTimeout(function () {
			toast.classList.remove('is-visible');
			toast.setAttribute('hidden', '');
		}, 2800);
	}

	document.body.addEventListener('click', function (e) {
		var btn = e.target.closest('.add_to_cart_button');
		if (!btn || btn.classList.contains('loading')) {
			return;
		}
		btn.classList.add('loading');
		setTimeout(function () {
			btn.classList.remove('loading');
		}, 2000);
	});

	if (typeof jQuery !== 'undefined') {
		jQuery(document.body).on('added_to_cart', function () {
			showToast(cfg.addedToCart || 'Added to cart');
			openCartDrawer();
		});
	}

	/* Wishlist toggle */
	function applyWishlistFragments(fragments) {
		if (!fragments || typeof fragments !== 'object') {
			return;
		}
		Object.keys(fragments).forEach(function (selector) {
			var el = document.querySelector(selector);
			if (el) {
				el.outerHTML = fragments[selector];
			}
		});
	}

	document.body.addEventListener('click', function (e) {
		var btn = e.target.closest('.jwellery-wishlist-btn');
		if (!btn || btn.classList.contains('is-loading')) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();

		if (!cfg.isLoggedIn) {
			showToast(cfg.wishlistLoginMsg || 'Please log in to use wishlist');
			if (cfg.loginUrl) {
				setTimeout(function () {
					window.location.href = cfg.loginUrl;
				}, 900);
			}
			return;
		}

		var productId = btn.getAttribute('data-product-id');
		if (!productId || !cfg.ajaxUrl || !cfg.nonce) {
			return;
		}

		btn.classList.add('is-loading');
		var body = new URLSearchParams();
		body.append('action', 'jwellery_toggle_wishlist');
		body.append('nonce', cfg.nonce);
		body.append('product_id', productId);

		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
			.then(function (res) {
				return res.json();
			})
			.then(function (data) {
				btn.classList.remove('is-loading');
				if (!data || !data.success) {
					showToast((data && data.data && data.data.message) || 'Unable to update wishlist');
					if (data && data.data && data.data.loginUrl) {
						setTimeout(function () {
							window.location.href = data.data.loginUrl;
						}, 900);
					}
					return;
				}
				var added = data.data && data.data.added;
				btn.classList.toggle('is-active', !!added);
				btn.setAttribute('aria-pressed', added ? 'true' : 'false');
				document.querySelectorAll('.jwellery-wishlist-btn[data-product-id="' + productId + '"]').forEach(function (other) {
					other.classList.toggle('is-active', !!added);
					other.setAttribute('aria-pressed', added ? 'true' : 'false');
				});
				showToast((data.data && data.data.message) || (added ? cfg.wishlistAddedMsg : cfg.wishlistRemovedMsg));
				applyWishlistFragments(data.data && data.data.fragments);
			})
			.catch(function () {
				btn.classList.remove('is-loading');
				showToast('Unable to update wishlist');
			});
	});

	/* Sticky add to cart on product page */
	var sticky = document.getElementById('jwellery-sticky-atc');
	var atcMain = document.querySelector('.single-product .single_add_to_cart_button, .single-product .add_to_cart_button');
	if (sticky && atcMain) {
		sticky.removeAttribute('hidden');
		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					var visible = !entry.isIntersecting;
					sticky.classList.toggle('is-visible', visible);
					document.body.classList.toggle('jwellery-sticky-atc-visible', visible);
				});
			},
			{ threshold: 0 }
		);
		observer.observe(atcMain);
	}

	/* Focus visible for keyboard */
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Tab') {
			document.body.classList.add('jwellery-keyboard-nav');
		}
	});

	/* Scroll reveal animations — tuned for mobile & tablet */
	var isTouchLayout = window.matchMedia('(max-width: 1024px)').matches;
	var revealObserver = null;
	var revealSelectors = '.jwellery-reveal, .jwellery-reveal-child, .jwellery-reveal-scale, .jwellery-reveal-fade, .jwellery-reveal-slide-right, .jwellery-reveal-slide-left, [data-animate="carousel"]';

	function markRevealVisible(el) {
		el.classList.add('is-visible');
		if (revealObserver) {
			revealObserver.unobserve(el);
		}
	}

	function revealElementsInView() {
		document.querySelectorAll(revealSelectors).forEach(function (el) {
			if (el.classList.contains('is-visible')) {
				return;
			}
			var rect = el.getBoundingClientRect();
			var vh = window.innerHeight || document.documentElement.clientHeight;
			if (rect.top < vh * 0.94 && rect.bottom > 8) {
				markRevealVisible(el);
			}
		});
	}

	if ('IntersectionObserver' in window) {
		revealObserver = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						markRevealVisible(entry.target);
					}
				});
			},
			isTouchLayout
				? { threshold: 0.04, rootMargin: '0px 0px 6% 0px' }
				: { threshold: 0.1, rootMargin: '0px 0px -24px 0px' }
		);
	}

	function observeReveal(el, delay, className) {
		el.classList.add(className || 'jwellery-reveal');
		el.style.setProperty('--reveal-delay', delay + 's');
		if (revealObserver) {
			revealObserver.observe(el);
		} else {
			el.classList.add('is-visible');
		}
	}

	function staggerReveal(selector, className, step) {
		document.querySelectorAll(selector).forEach(function (el, i) {
			observeReveal(el, Math.min(i * (step || 0.08), 0.48), className);
		});
	}

	/* Section headers — underline + fade */
	document.querySelectorAll('[data-animate="head"]').forEach(function (head, i) {
		observeReveal(head, Math.min(i * 0.04, 0.2), 'jwellery-reveal-fade');
	});

	/* Carousels & grids after header */
	document.querySelectorAll('[data-animate="carousel"]').forEach(function (el, i) {
		if (revealObserver) {
			el.classList.add('jwellery-reveal');
			el.style.setProperty('--reveal-delay', '0.12s');
			revealObserver.observe(el);
		} else {
			el.classList.add('is-visible');
		}
	});

	staggerReveal('.jwellery-budget-card', 'jwellery-reveal-scale', 0.1);
	staggerReveal('.jwellery-trust-strip-grid li', 'jwellery-reveal-child', 0.09);
	staggerReveal('.jwellery-product-grid--static .product', 'jwellery-reveal-child', 0.07);
	staggerReveal('.jwellery-carousel .product', 'jwellery-reveal-child', 0.06);
	staggerReveal('.jwellery-follow-item', 'jwellery-reveal-child', 0.08);
	staggerReveal('.jwellery-faq-item', 'jwellery-reveal-child', 0.06);
	staggerReveal('.jwellery-faq-cta', 'jwellery-reveal-slide-left', 0.15);
	staggerReveal('.category-card', 'jwellery-reveal-child', 0.08);
	staggerReveal('.product-of-day-spotlight', 'jwellery-reveal-scale', 0.1);
	staggerReveal('.jwellery-review-card', 'jwellery-reveal-child', 0.05);
	staggerReveal('.jwellery-category-browse-card', 'jwellery-reveal-child', 0.07);

	requestAnimationFrame(revealElementsInView);
	window.addEventListener('load', revealElementsInView);
	window.addEventListener('resize', revealElementsInView);
	setTimeout(revealElementsInView, 350);
	setTimeout(revealElementsInView, 900);

	/* Popular products filter tabs */
	document.querySelectorAll('[data-popular-tabs]').forEach(function (section) {
		var tabs = section.querySelectorAll('.jwellery-product-tab');
		var panels = section.querySelectorAll('.jwellery-product-tab-panel');
		if (!tabs.length || !panels.length) {
			return;
		}
		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				var key = tab.getAttribute('data-tab');
				tabs.forEach(function (t) {
					var active = t === tab;
					t.classList.toggle('is-active', active);
					t.setAttribute('aria-selected', active ? 'true' : 'false');
				});
				panels.forEach(function (panel) {
					var show = panel.getAttribute('data-tab-panel') === key;
					panel.classList.toggle('is-active', show);
					if (show) {
						panel.removeAttribute('hidden');
					} else {
						panel.setAttribute('hidden', '');
					}
				});
			});
		});
	});

	/* Alternating section containers */
	document.querySelectorAll('.jwellery-main > .jwellery-home-section > .container').forEach(function (el, i) {
		var cls = i % 2 === 0 ? 'jwellery-reveal-slide-right' : 'jwellery-reveal-slide-left';
		observeReveal(el, 0.05, cls);
	});

	document.querySelectorAll('.jwellery-trust-strip > .container').forEach(function (el) {
		observeReveal(el, 0, 'jwellery-reveal-fade');
	});

	document.querySelectorAll('.jwellery-footer-grid').forEach(function (el) {
		observeReveal(el, 0.1, 'jwellery-reveal-fade');
	});

	/* Testimonials marquee — pause on touch */
	document.querySelectorAll('[data-testimonials-marquee]').forEach(function (wrap) {
		var track = wrap.querySelector('.jwellery-testimonials-marquee-track');
		if (!track) {
			return;
		}
		wrap.addEventListener('mouseenter', function () {
			wrap.classList.add('is-paused');
		});
		wrap.addEventListener('mouseleave', function () {
			wrap.classList.remove('is-paused');
		});
		wrap.addEventListener('touchstart', function () {
			wrap.classList.add('is-paused');
		}, { passive: true });
		wrap.addEventListener('touchend', function () {
			setTimeout(function () {
				wrap.classList.remove('is-paused');
			}, 2000);
		});
	});

	/* Mini cart drawer */
	var cartDrawer = document.getElementById('jwellery-cart-drawer');

	function openCartDrawer() {
		if (!cartDrawer) {
			return;
		}
		cartDrawer.removeAttribute('hidden');
		cartDrawer.classList.add('is-open');
		cartDrawer.setAttribute('aria-hidden', 'false');
		document.body.classList.add('jwellery-cart-open');
		document.querySelectorAll('.jwellery-cart-toggle').forEach(function (toggle) {
			toggle.setAttribute('aria-expanded', 'true');
		});
	}

	function closeCartDrawer() {
		if (!cartDrawer) {
			return;
		}
		cartDrawer.classList.remove('is-open');
		cartDrawer.setAttribute('aria-hidden', 'true');
		cartDrawer.setAttribute('hidden', '');
		document.body.classList.remove('jwellery-cart-open');
		document.querySelectorAll('.jwellery-cart-toggle').forEach(function (toggle) {
			toggle.setAttribute('aria-expanded', 'false');
		});
	}

	document.querySelectorAll('.jwellery-cart-toggle').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (cartDrawer && cartDrawer.classList.contains('is-open')) {
				closeCartDrawer();
			} else {
				openCartDrawer();
			}
		});
	});

	document.querySelectorAll('[data-cart-close]').forEach(function (el) {
		el.addEventListener('click', closeCartDrawer);
	});

	/* Quick view modal */
	var qvModal = document.getElementById('jwellery-quick-view');

	function closeQuickView() {
		if (!qvModal) {
			return;
		}
		qvModal.setAttribute('hidden', '');
		document.body.classList.remove('jwellery-qv-open');
	}

	function openQuickView(productId) {
		if (!qvModal || !productId || !cfg.ajaxUrl || !cfg.nonce) {
			return;
		}
		var inner = qvModal.querySelector('.jwellery-quick-view-inner');
		qvModal.removeAttribute('hidden');
		document.body.classList.add('jwellery-qv-open');
		inner.innerHTML = '<p class="jwellery-qv-loading">' + (cfg.quickViewLoading || 'Loading…') + '</p>';
		var url = cfg.ajaxUrl + '?action=jwellery_quick_view&product_id=' + encodeURIComponent(productId) + '&nonce=' + encodeURIComponent(cfg.nonce);
		fetch(url, { credentials: 'same-origin' })
			.then(function (res) {
				return res.json();
			})
			.then(function (data) {
				if (data && data.success && data.data && data.data.html) {
					inner.innerHTML = data.data.html;
				} else {
					inner.innerHTML = '<p>' + (cfg.quickViewLoading || 'Unable to load product.') + '</p>';
				}
			})
			.catch(function () {
				inner.innerHTML = '<p>' + (cfg.quickViewLoading || 'Unable to load product.') + '</p>';
			});
	}

	document.body.addEventListener('click', function (e) {
		var qvBtn = e.target.closest('.jwellery-quick-view-btn');
		if (qvBtn) {
			e.preventDefault();
			e.stopPropagation();
			openQuickView(qvBtn.getAttribute('data-product-id'));
			return;
		}
		if (e.target.closest('[data-qv-close]')) {
			closeQuickView();
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			closeCartDrawer();
			closeQuickView();
		}
	});

	/* Store pages — scroll reveal */
	if ('IntersectionObserver' in window) {
		var revealItems = document.querySelectorAll('.jwellery-animate-item');
		if (revealItems.length) {
			var revealObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-visible');
						revealObserver.unobserve(entry.target);
					}
				});
			}, { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

			revealItems.forEach(function (el) {
				revealObserver.observe(el);
			});
		}
	} else {
		document.querySelectorAll('.jwellery-animate-item').forEach(function (el) {
			el.classList.add('is-visible');
		});
	}

	/* FAQ — one item open at a time */
	document.querySelectorAll('.jwellery-faq-item').forEach(function (item) {
		item.addEventListener('toggle', function () {
			if (!item.open) {
				return;
			}
			document.querySelectorAll('.jwellery-faq-item').forEach(function (other) {
				if (other !== item && other.open) {
					other.removeAttribute('open');
				}
			});
		});
	});

	/* Floating scroll toggle */
	var scrollToggleBtn = document.querySelector('.jwellery-scroll-toggle');

	function getScrollBottom() {
		return Math.max(
			document.documentElement.scrollHeight,
			document.body.scrollHeight
		) - window.innerHeight;
	}

	function updateScrollToggle() {
		if (!scrollToggleBtn) {
			return;
		}
		var scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
		var maxScroll = getScrollBottom();
		var canScroll = maxScroll > 48;

		scrollToggleBtn.classList.toggle('is-hidden', !canScroll);
		if (!canScroll) {
			return;
		}

		var scrollUp = scrollY > maxScroll * 0.5;
		scrollToggleBtn.classList.toggle('is-scroll-up', scrollUp);
		scrollToggleBtn.classList.toggle('is-scroll-down', !scrollUp);
		scrollToggleBtn.setAttribute(
			'aria-label',
			scrollUp ? (cfg.scrollUp || 'Scroll to top') : (cfg.scrollDown || 'Scroll to bottom')
		);
	}

	if (scrollToggleBtn) {
		scrollToggleBtn.addEventListener('click', function () {
			var scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
			var maxScroll = getScrollBottom();
			var target = scrollY > maxScroll * 0.5 ? 0 : maxScroll;
			var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			window.scrollTo({ top: target, behavior: reduceMotion ? 'auto' : 'smooth' });
		});

		window.addEventListener('scroll', updateScrollToggle, { passive: true });
		window.addEventListener('resize', updateScrollToggle);
		updateScrollToggle();
	}

	/* My Account guest — tabs on mobile, both cards in one row on desktop */
	if (document.body.classList.contains('jwellery-account-page--guest')) {
		var guestTabs = document.querySelectorAll('.jwellery-uda-guest__tab[data-uda-tab]');
		var loginCol = document.querySelector('.jwellery-uda-guest__card .col-1');
		var registerCol = document.querySelector('.jwellery-uda-guest__card .col-2');
		var guestMq = window.matchMedia('(max-width: 640px)');
		var activeTab = window.location.hash === '#register' ? 'register' : 'login';

		function setGuestTab(name) {
			activeTab = name;
			guestTabs.forEach(function (tab) {
				var isActive = tab.getAttribute('data-uda-tab') === name;
				tab.classList.toggle('is-active', isActive);
				tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
			});
			if (loginCol) {
				loginCol.classList.toggle('is-active', name === 'login');
			}
			if (registerCol) {
				registerCol.classList.toggle('is-active', name === 'register');
			}
		}

		function syncGuestLayout() {
			if (guestMq.matches) {
				setGuestTab(activeTab);
				return;
			}
			if (loginCol) {
				loginCol.classList.add('is-active');
			}
			if (registerCol) {
				registerCol.classList.add('is-active');
			}
		}

		guestTabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				setGuestTab(tab.getAttribute('data-uda-tab'));
			});
		});

		if (typeof guestMq.addEventListener === 'function') {
			guestMq.addEventListener('change', syncGuestLayout);
		} else if (typeof guestMq.addListener === 'function') {
			guestMq.addListener(syncGuestLayout);
		}

		syncGuestLayout();
	}

	/* My Account — scroll to content top after section navigation */
	if (document.body.classList.contains('jwellery-account-page--logged-in')) {
		function scrollAccountToContent() {
			var main = document.getElementById('jwellery-account-main');
			if (!main) {
				return;
			}
			var top = main.getBoundingClientRect().top + (window.pageYOffset || 0) - 80;
			window.scrollTo({ top: Math.max(0, top), behavior: 'auto' });
		}

		document.querySelectorAll('.jwellery-uda__nav a, .jwellery-uda__mobnav-link, .jwellery-uda-action').forEach(function (link) {
			link.addEventListener('click', function () {
				try {
					sessionStorage.setItem('jwelleryAccountScrollTop', '1');
				} catch (err) { /* ignore */ }
			});
		});

		if (sessionStorage.getItem('jwelleryAccountScrollTop')) {
			try {
				sessionStorage.removeItem('jwelleryAccountScrollTop');
			} catch (err) { /* ignore */ }
			window.addEventListener('load', scrollAccountToContent);
		}
	}
})();
