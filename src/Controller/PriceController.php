<?php

/**
 * @file
 * Contains \Drupal\site_price\Controller\PriceController
 */

namespace Drupal\site_price\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;

class PriceController extends ControllerBase {

  /**
   * Страница отображения прайс-листа для администратора.
   */
  public function getPrice() {
    return array(
      '#theme' => 'site_price_admin_page',
      '#attached' => array(
        'library' => array(
          'site_price/site_price.admin',
        ),
      ),
      '#cache' => [
        'keys' => ['price', 'full'],
        'tags' => ['price'],
        'contexts' => ['languages', 'timezone'],
        'max-age' => Cache::PERMANENT,
      ],
    );
  }
}