<?php

namespace Drupal\site_price\TwigExtension;

/**
 * Twig extension that adds a custom function and a custom filter.
 */
class SitePriceTwigExtension extends \Twig_Extension {

  /**
   * Generates a list of all Twig functions that this extension defines.
   *
   * @return array
   *   A key/value array that defines custom Twig functions. The key denotes the
   *   function name used in the tag, e.g.:
   *   @code
   *   {{ testfunc() }}
   *   @endcode
   *
   *   The value is a standard PHP callback that defines what the function does.
   */
  public function getFunctions() {
    return array(
      'getPrice' => new \Twig_Function_Function(array('Drupal\site_price\TwigExtension\SitePriceTwigExtension', 'getPrice')),
      'getPriceFilter' => new \Twig_Function_Function(array('Drupal\site_price\TwigExtension\SitePriceTwigExtension', 'getPriceFilter')),
    );
  }

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   A unique identifier for this Twig extension.
   */
  public function getName() {
    return 'site_price.twig_extension';
  }

  /**
   * Формирует прайс-лист по умолчанию.
   */
  public static function getPrice($prid, $edit = FALSE) {
    return array(
      '#theme' => 'site_price',
      '#edit' => $edit,
      '#prid' => $prid,
      '#attached' => array(
        'library' => array(
          'site_price/site_price.module',
        ),
      ),
    );
  }

  /**
   * Формирует форму поиска-фильтрации позиций.
   */
  public static function getPriceFilter($prid, $edit = FALSE) {
    $form = \Drupal::formBuilder()->getForm('Drupal\site_price\Form\PricePositionFilterForm', $prid, $edit);
    return array(
      '#markup' => \Drupal::service('renderer')->render($form, FALSE),
    );
  }
}
