<?php

/**
 * @file
 * Contains \Drupal\site_price\Form\PriceForm
 */

namespace Drupal\site_price\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_price\Controller\PriceDatabaseController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Slider type class form.
 */
class PriceForm extends FormBase {

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
    return 'site_price_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $prid = 0) {
    // Загружаем объекты если переданы идентификаторы.
    $price = $this->databasePrice->loadPrice($prid);

    $form['price'] = array(
      '#type' => 'value',
      '#value' => $price,
    );

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#required' => FALSE,
      '#default_value' => isset($price->prid) ? $price->title : "",
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#required' => FALSE,
      '#default_value' => isset($price->prid) ? $price->description : "",
    );

    $form['status'] = array(
      '#title' => $this->t('Display'),
      '#type' => 'checkbox',
      '#default_value' => isset($price->prid) ? $price->status : 1,
    );

    // Now we add our submit button, for submitting the form results.
    // The 'actions' wrapper used here isn't strictly necessary for tabledrag,
    // but is included as a Form API recommended practice.
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );

    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#button_type' => 'danger',
      '#submit' => array('::submitDeleteForm'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitDeleteForm(array &$form, FormStateInterface $form_state) {
    // Проверяем нажатие кнопки удаления прайс-листа.
    $price = $form_state->getValue('price');
    if (isset($price->prid)) {
      $form_state->setRedirect('site_price.delete_confirm_price', array('prid' => $price->prid));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $price = $form_state->getValue('price');
    $title = trim($form_state->getValue('title'));

    // Выполняем если создаётся новый элемент.
    $return_created = null;
    $return_updated = null;

    if ($title) {
      if (!isset($price->prid)) {
        $uuid = \Drupal::service('uuid');
        $data = array(
          'uuid' => $uuid->generate(),
          'title' => $title,
          'description' => trim($form_state->getValue('description')),
          'created' => REQUEST_TIME,
          'changed' => REQUEST_TIME,
          'status' => $form_state->getValue('status'),
        );
        $return_created = $this->databasePrice->insertPrice($data);
        $price = $this->databasePrice->loadPrice($return_created);
      } else {
        // Выполняем если обновляется элемент.
        $entry = array(
          'prid' => $price->prid,
          'title' => $title,
          'description' => trim($form_state->getValue('description')),
          'changed' => REQUEST_TIME,
          'status' => $form_state->getValue('status'),
        );
        $return_updated = $this->databasePrice->updatePrice($entry);
      }
    }

    if ($return_created) {
      drupal_set_message($this->t('Price list «@title» created.', array('@title' => $form_state->getValue('title'))));
    }

    if ($return_updated) {
      drupal_set_message($this->t('Price list «@title» updated.', array('@title' => $form_state->getValue('title'))));
    }

    // Очищает cache.
    Cache::invalidateTags(['price']);

    $form_state->setRedirect('site_price.admin');
  }
}
