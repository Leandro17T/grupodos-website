<?php if ( ! defined('ABSPATH') ) exit; ?>
<div class="gdos-carousel-wrap">
  <?php if ( ! empty($title) ): ?>
    <div class="gdos-carousel-head">
      <h3 class="gdos-carousel-title"><?php echo esc_html($title); ?></h3>
    </div>
  <?php endif; ?>

  <div id="<?php echo esc_attr($id); ?>" class="gdos-carousel" data-gdos-carousel>
    <button class="gdos-nav gdos-nav--prev" aria-label="Anterior" type="button">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <div class="gdos-track" data-gdos-track>
      <?php foreach ( $products as $p ): ?>
        <article class="gdos-card">
          <a class="gdos-card__image" href="<?php echo esc_url($p['permalink']); ?>">
            <img src="<?php echo esc_url($p['image']); ?>" alt="<?php echo esc_attr($p['title']); ?>" loading="lazy" />
            <?php if ( $p['best_seller'] ): ?>
              <span class="gdos-badge gdos-badge--best">Más vendido</span>
            <?php endif; ?>
          </a>

          <a class="gdos-card__title" href="<?php echo esc_url($p['permalink']); ?>">
            <?php echo esc_html($p['title']); ?>
          </a>

          <div class="gdos-card__price">
            <?php if ( $p['on_sale'] && $p['regular_html'] ): ?>
              <span class="gdos-price--regular"><del><?php echo wp_kses_post($p['regular_html']); ?></del></span>
            <?php endif; ?>
            <span class="gdos-price--sale"><?php echo wp_kses_post($p['sale_html']); ?></span>

            <?php if ( $p['on_sale'] && $p['discount_pct'] > 0 ): ?>
              <span class="gdos-badge gdos-badge--discount">-<?php echo esc_html($p['discount_pct']); ?>%</span>
            <?php endif; ?>
          </div>

          <?php if ( $p['shipping_label'] ): ?>
            <div class="gdos-card__ship">
              <span class="gdos-ship">
                <svg class="gdos-ship__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M3 7h11v8H3zM14 9h4l3 3v3h-7zM5 19a2 2 0 110-4 2 2 0 010 4zm10 0a2 2 0 110-4 2 2 0 010 4z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" fill="none"/>
                </svg>
                <?php echo esc_html($p['shipping_label']); ?>
              </span>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>

    <button class="gdos-nav gdos-nav--next" aria-label="Siguiente" type="button">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
  </div>
</div>
