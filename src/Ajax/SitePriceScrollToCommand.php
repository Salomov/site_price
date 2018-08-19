<?php
namespace Drupal\site_price\Ajax;
use Drupal\Core\Ajax\CommandInterface;

class SitePriceScrollToCommand implements CommandInterface {
  protected $data;

  // Constructs a SitePriceScrollToCommand object.
  public function __construct($data) {
    $this->data = $data;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => 'sitePriceScrollTo',
      'object' => $this->data->object,
      'duration' => $this->data->duration,
    );
  }
}