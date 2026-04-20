/**
 * MD Nested Carousel — editor element-type registration.
 *
 * Mirrors Pro's pattern (elementor-pro/assets/js/editor.js):
 *
 *   elementorCommon.elements.$window.on(
 *       'elementor/nested-element-type-loaded',
 *       async () => new Module()  // registers 'nested-carousel'
 *   );
 *
 * Our script is enqueued separately from the main editor bundle, so by the
 * time it runs the events below may have already fired. We therefore:
 *   1. Subscribe to `elementor/nested-element-type-loaded` (primary).
 *   2. Subscribe to `elementor:init` (fallback — fires after full init).
 *   3. Call `register()` immediately (covers the case when both events have
 *      already fired; `register()` is idempotent and guards against missing
 *      `NestedElementBase` / `elementsManager`).
 *
 * Do NOT use `elementsManager.getElementTypeClass(type)` as an idempotency
 * guard — for unregistered types it returns a truthy fallback `Widget` class,
 * so the check would be a false positive and skip registration. Use the
 * `registered` flag instead.
 */
( function () {
	'use strict';

	var registered = false;

	function register() {
		if ( registered ) return;

		var NestedBase = window.elementor
			&& elementor.modules
			&& elementor.modules.elements
			&& elementor.modules.elements.types
			&& elementor.modules.elements.types.NestedElementBase;

		if ( ! NestedBase || ! elementor.elementsManager ) return;

		// Mirror Pro's class shape. `getChildType()` returns 'container' via
		// inheritance from NestedElementBase — do NOT override it.
		class MDNestedCarousel extends NestedBase {
			getType() { return 'md-nested-carousel'; }
		}

		elementor.elementsManager.registerElementType( new MDNestedCarousel() );
		registered = true;

		// Once the type is registered, wire up editor-only UX: scroll a
		// hidden slide into view when it (or its child) becomes selected.
		installAutoScrollToSelectedSlide();
	}

	// -------------------------------------------------------------------------
	// Editor UX: when a slide (or any element inside it) is selected — either
	// via click in the preview or via the Navigator — translate the wrapper so
	// the slide is centered and visible. Off-screen slides are clipped by
	// `.md-n-carousel { overflow: hidden }` in editor mode.
	// -------------------------------------------------------------------------
	function installAutoScrollToSelectedSlide() {
		var bind = function () {
			var iframe = window.elementor && elementor.$preview && elementor.$preview[ 0 ];
			if ( ! iframe ) return;
			var doc = iframe.contentDocument;
			if ( ! doc || ! doc.body ) return;
			if ( doc.__mdNCObserverInstalled ) return;
			doc.__mdNCObserverInstalled = true;

			var observer = new MutationObserver( function ( mutations ) {
				for ( var i = 0; i < mutations.length; i++ ) {
					var m = mutations[ i ];
					var el = m.target;
					if ( ! el.classList || ! el.classList.contains( 'elementor-element-edit-mode' ) ) continue;
					var slide = el.closest( '.elementor-widget-md-nested-carousel .swiper-slide' );
					if ( slide ) scrollSlideIntoView( slide );
				}
			} );
			observer.observe( doc.body, {
				subtree: true,
				attributes: true,
				attributeFilter: [ 'class' ],
			} );
		};

		bind();
		// Re-bind if Elementor reloads the preview iframe.
		if ( window.elementorCommon && elementorCommon.elements && elementorCommon.elements.$window ) {
			elementorCommon.elements.$window.on( 'elementor/preview/loaded', bind );
		}
	}

	function scrollSlideIntoView( slide ) {
		var wrapper = slide.parentNode;
		if ( ! wrapper || ! wrapper.classList.contains( 'swiper-wrapper' ) ) return;
		var carousel = wrapper.parentNode;
		if ( ! carousel ) return;

		// Swiper v8+ exposes its instance as `.swiper` on the container element.
		// Fallback to jQuery data() cache that our frontend handler populates.
		var swiper = carousel.swiper;
		if ( ! swiper && window.jQuery ) {
			try { swiper = window.jQuery( carousel ).data( 'swiper' ); } catch ( e ) {}
		}
		if ( ! swiper || typeof swiper.slideTo !== 'function' ) return;

		var slides = wrapper.querySelectorAll( ':scope > .swiper-slide' );
		var index  = Array.prototype.indexOf.call( slides, slide );
		if ( index < 0 ) return;

		// Debounce: avoid re-triggering for the same slide if multiple class
		// mutations fire for the same selection.
		if ( carousel.__mdNCLastIndex === index ) return;
		carousel.__mdNCLastIndex = index;

		swiper.slideTo( index );
	}

	function attach() {
		if ( ! window.elementorCommon || ! elementorCommon.elements || ! elementorCommon.elements.$window ) {
			return false;
		}

		var $w = elementorCommon.elements.$window;

		$w.on( 'elementor/nested-element-type-loaded', register );
		$w.on( 'elementor:init',                        register );

		register();

		return true;
	}

	if ( ! attach() ) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', attach, { once: true } );
		} else {
			Promise.resolve().then( attach );
		}
	}
}() );
