<?php

/**
 * @file
 * Contains \Drupal\site_price\Form\PricePositionForm
 */

namespace Drupal\site_price\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_price\Controller\PriceDatabaseController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Step class form.
 */
class PricePositionForm extends FormBase {

  /**
   * The servises classes.
   *
   * @var \Drupal\site_price\Controller\PriceDatabaseController
   */
  protected $databasePrice;

  /**
   * Constructs a new DblogClearLogForm.
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_price_position_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pid = 0, $id = 0, $type = '') {
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

    // Текст примечание.
    $form['create_message'] = [
      '#markup' => '<div id="site-price-create-message">' . $this->t('The position will be established without reference to the group or category.') . '</div>',
      '#weight' => -50,
    ];

    // Загружаем объекты если передан идентификатор.
    $price_position = $this->databasePrice->loadPricePosition($pid);

    // Определяем данные для привязки позиции.
    if ($type == 'group') {
      $data = $this->databasePrice->loadPriceGroup($id);
      $form['gid'] = array(
        '#type' => 'value',
        '#value' => $data->gid,
      );

      // Текст примечание.
      $form['create_message'] = [
        '#markup' => '<div id="site-price-create-message">' . $this->t('Create position for group <strong>@title</strong>.', array('@title' => $data->title)) . '</div>',
        '#weight' => -50,
      ];

    }
    if ($type == 'category') {
      $data = $this->databasePrice->loadPriceCategory($id);
      $form['cid'] = array(
        '#type' => 'value',
        '#value' => $data->cid,
      );

      // Текст примечание.
      $form['create_message'] = [
        '#markup' => '<div id="site-price-create-message">' . $this->t('Create position for category <strong>@title</strong>.', array('@title' => $data->title)) . '</div>',
        '#weight' => -50,
      ];
    }

    $form['price_position'] = array(
      '#type' => 'value',
      '#value' => $price_position,
    );

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => isset($price_position->pid) ? $price_position->title : "",
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#required' => FALSE,
      '#default_value' => isset($price_position->pid) ? $price_position->description : "",
    );

    $form['code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Code'),
      '#maxlength' => 128,
      '#required' => FALSE,
      '#default_value' => isset($price_position->pid) ? $price_position->code : "",
    );

    $href = '';
    if (isset($price_position->pid) && $price_position->nid) {
        $href = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $price_position->nid);
    }
    $form['nid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search page number by name'),
      '#autocomplete_route_name' => 'kvantstudio.search_node_autocomplete',
      '#description' => $href,
      '#default_value' => isset($price_position->pid) ? $price_position->nid : "",
    ];

    $form['cost_settings'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('site-price-position-form__container')),
      '#weight' => 99,
    );

    $form['cost_prefix'] = array(
      '#type' => 'textfield',
      '#group' => 'cost_settings',
      '#title' => $this->t('Prefix'),
      '#description' => $this->t('The text that appears before the cost.'),
      '#maxlength' => 20,
      '#required' => FALSE,
      '#default_value' => isset($price_position->pid) ? $price_position->cost_prefix : "",
    );

    $form['cost_from'] = array(
      '#type' => 'textfield',
      '#group' => 'cost_settings',
      '#title' => $this->t('Cost from'),
      '#maxlength' => 10,
      '#required' => FALSE,
      '#default_value' => isset($price_position->pid) ? $price_position->cost_from : '0.00',
    );

    $form['cost'] = array(
      '#type' => 'textfield',
      '#group' => 'cost_settings',
      '#title' => $this->t('Cost'),
      '#description' => $this->t('Main or the maximum cost of the position.'),
      '#maxlength' => 10,
      '#required' => TRUE,
      '#default_value' => isset($price_position->pid) ? $price_position->cost : '0.00',
    );

    $form['cost_discount'] = array(
      '#type' => 'textfield',
      '#group' => 'cost_settings',
      '#title' => $this->t('Cost discount'),
      '#description' => $this->t('If you fill in, it will show the value of the field «Discount Price» instead of the value of the field «Price».'),
      '#maxlength' => 10,
      '#required' => TRUE,
      '#default_value' => isset($price_position->pid) ? $price_position->cost_discount : '0.00',
    );

    $form['cost_suffix'] = array(
      '#type' => 'textfield',
      '#group' => 'cost_settings',
      '#title' => $this->t('Suffix'),
      '#description' => $this->t('The text that appears after the cost.'),
      '#maxlength' => 20,
      '#required' => FALSE,
      '#default_value' => isset($price_position->pid) ? $price_position->cost_suffix : "",
    );

    $form['free'] = array(
      '#type' => 'checkbox',
      '#group' => 'cost_settings',
      '#title' => $this->t('Free'),
      '#default_value' => isset($price_position->free) ? $price_position->free : 0,
    );

    // Добавляем элемент где будем выводить системные сообщения.
    $form['system_message'] = [
      '#markup' => '<div id="site-price-position-message"></div>',
    ];

    // Now we add our submit button, for submitting the form results.
    // The 'actions' wrapper used here isn't strictly necessary for tabledrag,
    // but is included as a Form API recommended practice.
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getValue('title') == '') {
      $form_state->setErrorByName('title', $this->t("Field «Title» required."));
      $response->addCommand(new HtmlCommand('#site-price-position-message', $this->t("Field «Title» required.")));
    }

    return $response;
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
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $price_position = $form_state->getValue('price_position');

    $title = trim($form_state->getValue('title'));

    $nid = $form_state->getValue('nid');
    if (!isset($nid) || !is_numeric($nid)) {
      $nid = 0;
    }

    // Выполняем создание.
    $return_created = null;
    $return_updated = null;
    if (!isset($price_position->pid) && $title) {
      $uuid = \Drupal::service('uuid');
      $data = array(
        'uuid' => $uuid->generate(),
        'title' => $title,
        'description' => trim($form_state->getValue('description')),
        'code' => trim($form_state->getValue('code')),
        'cost_from' => round(trim($form_state->getValue('cost_from')), 2),
        'cost' => round(trim($form_state->getValue('cost')), 2),
        'cost_discount' => round(trim($form_state->getValue('cost_discount')), 2),
        'created' => REQUEST_TIME,
        'changed' => REQUEST_TIME,
        'status' => 1,
        'free' => $form_state->getValue('free'),
        'cost_prefix' => trim($form_state->getValue('cost_prefix')),
        'cost_suffix' => trim($form_state->getValue('cost_suffix')),
        'nid' => $nid,
      );
      $return_created = $this->databasePrice->insertPricePosition($data);

      // Выполняем создание привязки.
      if ($form_state->getValue('gid') || $form_state->getValue('cid')) {
        if ($return_created) {
          $insert = array(
            'gid' => $form_state->getValue('gid'),
            'cid' => $form_state->getValue('cid'),
            'pid' => $return_created,
          );
          $this->databasePrice->insertPriceHierarchy($insert);
        }
      }

    } else {
      // Выполняем обновление.
      $entry = array(
        'pid' => $price_position->pid,
        'title' => $title,
        'description' => trim($form_state->getValue('description')),
        'code' => trim($form_state->getValue('code')),
        'cost_from' => round(trim($form_state->getValue('cost_from')), 2),
        'cost' => round(trim($form_state->getValue('cost')), 2),
        'cost_discount' => round(trim($form_state->getValue('cost_discount')), 2),
        'changed' => REQUEST_TIME,
        'status' => 1,
        'free' => $form_state->getValue('free'),
        'cost_prefix' => trim($form_state->getValue('cost_prefix')),
        'cost_suffix' => trim($form_state->getValue('cost_suffix')),
        'nid' => $nid,
      );
      $return_updated = $this->databasePrice->updatePricePosition($entry);
    }

    // Очищает cache.
    Cache::invalidateTags(['price']);

    $response = new AjaxResponse();

    // Загружает прайс-лист по идентификатору.
    $prid = \Drupal::state()->get('site_price.last_price_load', 0);
    if ($prid) {
      $renderData = array(
        '#theme' => 'site_price',
        '#prid' => $prid,
        '#edit' => TRUE,
        '#filter' => '',
      );
      $price = $renderer->render($renderData);
      $response->addCommand(new HtmlCommand('.price', $price));
    }

    $message = '';
    if ($return_created) {
      $message = $this->t('Position <strong>@title</strong> created.', array('@title' => trim($form_state->getValue('title'))));
      $response->addCommand(new HtmlCommand('#site-price-position-message', $message));
      $response->addCommand(new InvokeCommand('.form-text', 'val', array('')));
      $response->addCommand(new InvokeCommand('input[name="cost_from"]', 'val', array('0.00')));
      $response->addCommand(new InvokeCommand('input[name="cost"]', 'val', array('0.00')));
      $response->addCommand(new InvokeCommand('input[name="cost_discount"]', 'val', array('0.00')));
    }
    if ($return_updated) {
      $message = $this->t('Position <strong>@title</strong> updated.', array('@title' => trim($form_state->getValue('title'))));
      $response->addCommand(new BeforeCommand('#price__position-title-' . $price_position->pid, '<i class="fa fa-floppy-o" aria-hidden="true"></i> ' . $message));
      $response->addCommand(new CssCommand('#price__position-' . $price_position->pid, array('background' => '#f9f9f9', 'border' => '1px solid #fcefa1')));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }
}
