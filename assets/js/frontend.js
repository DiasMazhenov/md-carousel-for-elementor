( function ( $ ) {
	'use strict';

	class MDNestedCarouselHandler extends elementorModules.frontend.handlers.CarouselBase {

		getDefaultSettings() {
			const settings = super.getDefaultSettings();
			settings.selectors.carousel      = '.md-n-carousel';
			settings.selectors.slidesWrapper = '.md-n-carousel > .swiper-wrapper';
			settings.selectors.slideContent  = '.swiper-slide';
			return settings;
		}

		getSwiperSettings() {
			const options         = super.getSwiperSettings();
			const elementSettings = this.getElementSettings();
			const isRtl           = elementorFrontend.config.is_rtl;
			const widgetSelector  = `.elementor-element-${ this.getID() }`;

			// In editor: disable autoplay/loop, prevent swiper from
			// intercepting drag-and-drop on nested widgets.
			if ( elementorFrontend.isEditMode() ) {
				delete options.autoplay;
				options.loop             = false;
				options.noSwipingSelector = '.swiper-slide > .e-con .elementor-element';
			}

			// Transition effect (slide / fade / coverflow / flip / cube / cards).
			if ( elementSettings.effect && 'slide' !== elementSettings.effect ) {
				options.effect = elementSettings.effect;

				if ( 'fade' === elementSettings.effect ) {
					options.fadeEffect = { crossFade: true };
				}

				if ( 'coverflow' === elementSettings.effect ) {
					options.coverflowEffect = {
						rotate: 40,
						stretch: 0,
						depth: 120,
						modifier: 1,
						slideShadows: true,
					};
				}

				if ( 'cards' === elementSettings.effect ) {
					options.cardsEffect = { perSlideOffset: 8, perSlideRotate: 2 };
				}
			}

			if ( 'yes' === elementSettings.arrows ) {
				options.navigation = {
					prevEl: isRtl
						? `${ widgetSelector } .md-carousel-button-next`
						: `${ widgetSelector } .md-carousel-button-prev`,
					nextEl: isRtl
						? `${ widgetSelector } .md-carousel-button-prev`
						: `${ widgetSelector } .md-carousel-button-next`,
				};
			}

			if ( ! elementorFrontend.isEditMode() ) {
				if ( this._isTouchDevice() ) {
					options.touchRatio      = 1;
					options.longSwipesRatio = 0.3;
					options.followFinger    = true;
					options.threshold       = 10;
				} else {
					options.shortSwipes = false;
				}
			}

			return options;
		}

		async onInit( ...args ) {
			await super.onInit( ...args );
		}

		async initSwiper() {
			const Swiper = elementorFrontend.utils.swiper;
			this.swiper  = await new Swiper(
				this.elements.$swiperContainer[ 0 ],
				this.getSwiperSettings()
			);
			this.elements.$swiperContainer.data( 'swiper', this.swiper );
		}

		bindEvents() {
			super.bindEvents();
			elementorFrontend.elements.$window.on(
				'elementor/nested-container/atomic-repeater',
				this._onRepeaterAction.bind( this )
			);
		}

		async _onRepeaterAction( event ) {
			const { container, index, targetContainer, action: { type } } = event.detail;

			if ( container.model.get( 'id' ) !== this.$element.data( 'id' ) ) {
				return;
			}

			const { $slides } = this.getDefaultElements();
			let wrapperEl, contentEl;

			switch ( type ) {
				case 'move':
					wrapperEl = $slides[ index ];
					contentEl = targetContainer.view.$el[ 0 ];
					break;
				case 'duplicate':
					wrapperEl = $slides[ index + 1 ];
					contentEl = targetContainer.view.$el[ 0 ];
					break;
			}

			if ( wrapperEl && contentEl ) {
				wrapperEl.appendChild( contentEl );
			}

			$slides.each( ( i, el ) => el.setAttribute( 'data-slide', i + 1 ) );

			const buttons = this.$element[ 0 ].querySelectorAll( '.md-carousel-button' );
			buttons.forEach( btn => btn.classList.toggle( 'hide', 1 === $slides.length ) );

			if ( this.swiper && ! this.swiper.destroyed ) {
				this.swiper.destroy( true, true );
				this.swiper = null;
			}
			if ( $slides.length > 0 ) {
				await this.initSwiper();
			}
		}

		togglePauseOnHover( on ) {
			if ( elementorFrontend.isEditMode() ) return;
			super.togglePauseOnHover( on );
		}

		_isTouchDevice() {
			return !! elementorFrontend.utils.environment.isTouchDevice;
		}
	}

	$( window ).on( 'elementor/frontend/init', () => {
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/md-nested-carousel.default',
			( $element ) => {
				elementorFrontend.elementsHandler.addHandler( MDNestedCarouselHandler, { $element } );
			}
		);
	} );

}( jQuery ) );
