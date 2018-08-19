<?php

/**
 * @file
 * Contains \Drupal\site_price\Controller\PriceAjaxController
 */

namespace Drupal\site_price\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PriceAjaxController extends ControllerBase {

  /**
   * The servises classes.
   *
   * @var \Drupal\site_price\Controller\PriceDatabaseController
   */
  protected $databasePrice;

  /**
   * Construct.
   *
   * @param \Drupal\site_price\Controller\PriceDatabaseController $connection
   *   The database connection.
   */
  public function __construct(PriceDatabaseController $databasePrice) {
    $this->databasePrice = $databasePrice;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('site_price.database')
    );
  }

  /**
   * Регистрирует вес позиций в группе.
   *
   * @param  [string]   $method
   * @param  [int]      $gid
   * @param  [string]   $pids
   *
   * @return ajax response
   *
   * @access public
   */
  public function groupPositionsSetWeight($method, $gid, $pids) {
    if ($method == 'ajax') {
      // Create AJAX Response object.
      $response = new AjaxResponse();

      $pids = explode("&pid=", $pids);
      unset($pids[0]);

      $weight = 1;
      foreach ($pids as $pid) {
        //$response->addCommand(new AlertCommand("Категория: " . $gid . "; Позиция: " . $pid . "; Номер: " . $weight));
        $data = array(
          'gid' => $gid,
          'pid' => $pid,
          'weight' => $weight,
        );
        $this->databasePrice->groupPositionSetWeight($data);
        $weight++;
      }

      // Очищает cache.
      Cache::invalidateTags(['price']);

      // Return ajax response.
      return $response;
    }
  }

  /**
   * Регистрирует вес позиций в категории.
   *
   * @param  [string]   $method
   * @param  [int]      $cid
   * @param  [string]   $pids
   *
   * @return ajax response
   *
   * @access public
   */
  public function categoryPositionsSetWeight($method, $cid, $pids) {
    if ($method == 'ajax') {
      // Create AJAX Response object.
      $response = new AjaxResponse();

      $pids = explode("&pid=", $pids);
      unset($pids[0]);

      $weight = 1;
      foreach ($pids as $pid) {
        //$response->addCommand(new AlertCommand("Категория: " . $cid . "; Позиция: " . $pid . "; Номер: " . $weight));
        $data = array(
          'cid' => $cid,
          'pid' => $pid,
          'weight' => $weight,
        );
        $this->databasePrice->categoryPositionSetWeight($data);
        $weight++;
      }

      // Очищает cache.
      Cache::invalidateTags(['price']);

      // Return ajax response.
      return $response;
    }
  }
}