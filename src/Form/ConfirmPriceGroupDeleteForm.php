<?php

/**
 * @file
 * Contains \Drupal\site_price\Form\ConfirmPriceGroupDeleteForm
 */

namespace Drupal\site_price\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\site_price\Controller\PriceDatabaseController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirm group delete form.
 */
class ConfirmPriceGroupDeleteForm extends ConfirmFormBase {

  /**
   * The ID of the parametrs.
   *
   * @var integral
   */
  protected $gid;

  /**
   * The object to delete.
   *
   * @var object
   */
  protected $group;

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
    return 'price_group_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $gid = 0) {
    $this->gid = $gid;
    if ($this->gid) {
      $this->group = $this->databasePrice->loadPriceGroup($this->gid);      
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete «@title»?', array('@title' => $this->group->title));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('site_price.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->databasePrice->deletePriceGroup($this->group->gid);

    // Очищает cache.
    Cache::invalidateTags(['price']);

    drupal_set_message($this->t('Group «@title» deleted.', array('@title' => $this->group->title)));
    $form_state->setRedirect('site_price.admin');
  }
}