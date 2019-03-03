<?php

/**
 * @file
 * Contains Drupal\site_price\Form\PricePositionSearchForm
 */

namespace Drupal\site_price\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class PricePositionSearchForm extends FormBase {

  public function getFormId() {
    return 'site_price_position_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = 0, $type = '') {
    // Параметры по умолчанию.
    $data = NULL;
    $form['gid'] = array(
      '#type' => 'value',
      '#value' => 0,
    );
    $form['cid'] = array(
      '#type' => 'value',
      '#value' => 0,
    );

    // Определяем данные для привязки позиции.
    $databasePrice = \Drupal::service('site_price.database');
    if ($type == 'group') {
      $data = $databasePrice->loadPriceGroup($id);
      $form['gid'] = array(
        '#type' => 'value',
        '#value' => $data->gid,
      );
    }
    if ($type == 'category') {
      $data = $databasePrice->loadPriceCategory($id);
      $form['cid'] = array(
        '#type' => 'value',
        '#value' => $data->cid,
      );
    }

    $form['data'] = array(
      '#type' => 'value',
      '#value' => $data,
    );

    $form['search_text_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search position'),
      '#title_display' => 'invisible',
      '#autocomplete_route_name' => 'site_price.search_price_position_autocomplete',
      '#attributes' => array('placeholder' => $this->t('Search position')),
      '#maxlength' => 60,
      '#size' => 60,
    ];

    // Добавляем элемент где будем выводить системные сообщения.
    $form['system_message'] = [
      '#markup' => '<div id="site-price-position-search-message"></div>',
    ];

    // Now we add our submit button, for submitting the form results.
    // The 'actions' wrapper used here isn't strictly necessary for tabledrag,
    // but is included as a Form API recommended practice.
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#ajax' => [
        'callback' => '::ajaxSubmitCallback',
      ],
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    $databasePrice = \Drupal::service('site_price.database');

    $data = $form_state->getValue('data');

    $text = $form_state->getValue('search_text_field');
    $text = str_replace('"', '', $text);

    // Получение номера позиции.
    $array = explode(' ', $text);
    $number = $array[0];
    $number = str_replace('(', '', $number);
    $pid = (int) str_replace(')', '', $number);

    // Загружаем позицию по имени.
    $price_position = $databasePrice->loadPricePosition($pid);

    // Выполняем создание.
    if (isset($price_position->pid)) {
      $insert = array(
        'gid' => $form_state->getValue('gid'),
        'cid' => $form_state->getValue('cid'),
        'pid' => $price_position->pid,
      );
      $return_created = $databasePrice->insertPriceHierarchy($insert);
    }

    // Очищает cache.
    Cache::invalidateTags(['price']);

    $response = new AjaxResponse();

    if (isset($price_position->pid)) {
      $messages = $this->t('Added a link between <strong>@title</strong>.', array('@title' => $data->title . ' : ' . $price_position->title));
      $response->addCommand(new HtmlCommand('#site-price-position-search-message', $messages));
      $response->addCommand(new InvokeCommand('.ui-autocomplete-input', 'val', array('')));
    }

    return $response;
  }
}
