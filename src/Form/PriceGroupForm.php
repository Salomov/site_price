<?php

/**
 * @file
 * Contains \Drupal\site_price\Form\PriceGroupForm
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
class PriceGroupForm extends FormBase {

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
        return 'site_price_group_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $gid = 0) {
        // Проверяет наличие созданных прайс-листов.
        $prices = $this->databasePrice->loadPriceLists();
        if (!count($prices)) {
            drupal_set_message(t('Create your first price list in the form below.'), 'status');
            return $this->redirect('site_price.create_price');
        }

        // Загружаем объекты если переданы идентификаторы.
        $price_group = $this->databasePrice->loadPriceGroup($gid);
        
        $form['price_group'] = array(
            '#type' => 'value',
            '#value' => $price_group,
        );

        $form['prid'] = array(
            '#title' => $this->t('Price list'),
            '#type' => 'select',
            '#default_value' => isset($price_group->prid) ? $price_group->prid : 0,            
            '#options' => $this->databasePrice->loadPriceLists(),
        );        

        $form['title'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#maxlength' => 255,
            '#required' => true,
            '#default_value' => isset($price_group->gid) ? $price_group->title : "",
        );

        $form['status'] = array(
            '#title' => $this->t('Display'),
            '#type' => 'checkbox',
            '#default_value' => isset($price_group->gid) ? $price_group->status : 1,
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

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {}

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $price_group = $form_state->getValue('price_group');

        $title = trim($form_state->getValue('title'));

        // Выполняем если создаётся новый элемент.
        $return_created = null;
        $return_updated = null;
        if (!isset($price_group->gid) && $title) {
            $uuid = \Drupal::service('uuid');
            $data = array(
                'uuid' => $uuid->generate(),
                'prid' => $form_state->getValue('prid'),
                'title' => $title,
                'description' => '',
                'created' => REQUEST_TIME,
                'changed' => REQUEST_TIME,
                'status' => $form_state->getValue('status'),
                'weight' => 0,
            );
            $return_created = $this->databasePrice->insertPriceGroup($data);
            $price_group = $this->databasePrice->loadPriceGroup($return_created);
        } else {
            // Выполняем если обновляется элемент.
            $entry = array(
                'gid' => $price_group->gid,
                'prid' => $form_state->getValue('prid'),
                'title' => $title,                
                'changed' => REQUEST_TIME,
                'status' => $form_state->getValue('status'),                
            );
            $return_updated = $this->databasePrice->updatePriceGroup($entry);
        }

        if ($return_created) {
            drupal_set_message($this->t('Group «@title» created.', array('@title' => $form_state->getValue('title'))));
        }

        if ($return_updated) {
            drupal_set_message($this->t('Group «@title» updated.', array('@title' => $form_state->getValue('title'))));
        }

        // Очищает cache.
        Cache::invalidateTags(['price']);

        $form_state->setRedirect('site_price.admin');
    }
}
