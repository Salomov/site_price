<?php
/**
 * @file
 * Contains \Drupal\site_price\Controller\PricePositionSearchAutocomplete
 */

namespace Drupal\site_price\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Database;
use Drupal\kvantstudio\Formatter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PricePositionSearchAutocomplete {

  /**
   * {@inheritdoc}
   */
  public function autocomplete(Request $request) {
    // Получаем строку запроса ($_GET['q']).
    $string = $request->query->get('q');

    $matches = [];
    $matches_id = [];

    if ($string) {
      $string_array = explode(" ", $string);
      $request = '';
      $arguments = array();
      $i = 1;
      $count_array_string = count($string_array);
      foreach ($string_array as $key) {
        $arguments[':key' . $i] = $key;
        if ($count_array_string > $i) {
          $request .= 'INSTR(i.title, :key' . $i . ') > 0 AND ';
        } else {
          $request .= 'INSTR(i.title, :key' . $i . ') > 0';
        }
        $i++;
      }

      $query = \Drupal::database()->select('site_price_positions', 'i');
      $query->fields('i', array('pid', 'title', 'code', 'cost'));
      $query->where($request, $arguments);
      $query->orderBy('i.title', 'ASC');
      $query->range(0, 15);
      $result = $query->execute();

      foreach ($result as $row) {
        if (!array_key_exists($row->pid, $matches_id)) {
          $matches_id[$row->pid] = $row->pid;

          $cost = Formatter::price($row->cost);
          $value = Html::escape($row->title);

          if ($row->code) {
            $matches[] = ['value' => $value, 'label' => $row->pid . ' : ' . $row->code . ' : ' . $value . ' (' . $cost . ' руб.)'];
          } else {
            $matches[] = ['value' => $value, 'label' => $row->pid . ' : ' . $value . ' (' . $cost . ' руб.)'];
          }

        }
      }
    }

    return new JsonResponse($matches);
  }
}