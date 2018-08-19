<?php

/**
 * @file
 * Contains \Drupal\site_price\Form\PriceCategoryForm
 */

namespace Drupal\site_price\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_price\Controller\PriceDatabaseController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Step class form.
 */
class PriceCategoryForm extends FormBase {

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
        return 'site_price_category_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $cid = 0) {
        // Проверяет наличие созданных прайс-листов.
        $prices = $this->databasePrice->loadPriceLists();
        if (!count($prices)) {
            drupal_set_message(t('Create your first price list in the form below.'), 'status');
            return $this->redirect('site_price.create_price');
        }

        // Загружаем объекты если переданы идентификаторы.        
        $price_category = $this->databasePrice->loadPriceCategory($cid);

        $form['price_category'] = array(
            '#type' => 'value',
            '#value' => $price_category,
        );

        $form['gid'] = array(
            '#title' => $this->t('Group'),
            '#type' => 'select',
            '#default_value' => isset($price_category->gid) ? $price_category->gid : 0,
            '#description' => $this->t('Select group of price list.'),
            '#options' => $this->databasePrice->loadPriceGroups(),
        );

        $form['title'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#maxlength' => 255,
            '#required' => TRUE,
            '#default_value' => isset($price_category->cid) ? $price_category->title : "",
        );

        $form['description'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Description'),
            '#maxlength' => 255,
            '#required' => FALSE,
            '#default_value' => isset($price_category->cid) ? $price_category->description : "",
        );

        $form['cost'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Cost'),
            '#maxlength' => 10,
            '#required' => TRUE,
            '#default_value' => isset($price_category->cid) ? $price_category->cost : '0.00',
        );

        $form['cost_discount'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Cost discount'),
            '#maxlength' => 10,
            '#required' => TRUE,
            '#default_value' => isset($price_category->cid) ? $price_category->cost_discount : '0.00',
        );

        $form['status_cost'] = array(
            '#title' => $this->t('Display cost of positions'),
            '#type' => 'checkbox',
            '#default_value' => isset($price_category->cid) ? $price_category->status_cost : 1,
        );        

        $form['status'] = array(
            '#title' => $this->t('Display in the price list'),
            '#type' => 'checkbox',
            '#default_value' => isset($price_category->cid) ? $price_category->status : 1,
        );

        // Добавляем элемент где будем выводить системные сообщения.
        $form['system_message'] = [
            '#markup' => '<div id="site-price-category-message"></div>',
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
        $price_category = $form_state->getValue('price_category');

        $price_group = $this->databasePrice->loadPriceGroup($form_state->getValue('gid'));

        $title = trim($form_state->getValue('title'));
        
        // Выполняем создание.
        $return_created = null;
        $return_updated = null;
        if (!isset($price_category->cid) && $title) {
            $uuid = \Drupal::service('uuid');
            $data = array(
                'uuid' => $uuid->generate(),
                'prid' => $price_group->prid,
                'gid' => $form_state->getValue('gid'),
                'title' => $title,
                'description' => trim($form_state->getValue('description')),
                'cost' => round(trim($form_state->getValue('cost')), 2),
                'cost_discount' => round(trim($form_state->getValue('cost_discount')), 2),
                'created' => REQUEST_TIME,
                'changed' => REQUEST_TIME,
                'status' => $form_state->getValue('status'),
                'status_cost' => $form_state->getValue('status_cost'),                
                'weight' => 0,
            );
            $return_created = $this->databasePrice->insertPriceCategory($data);
            $price_category = $this->databasePrice->loadPriceCategory($return_created);
        } else {
            // Выполняем обновление.
            $entry = array(
                'cid' => $price_category->cid,
                'prid' => $price_group->prid,
                'gid' => $form_state->getValue('gid'),                
                'title' => $title,
                'description' => trim($form_state->getValue('description')),
                'cost' => round(trim($form_state->getValue('cost')), 2),
                'cost_discount' => round(trim($form_state->getValue('cost_discount')), 2),
                'changed' => REQUEST_TIME,
                'status' => $form_state->getValue('status'),
                'status_cost' => $form_state->getValue('status_cost'),
                'weight' => 0,
            );
            $return_updated = $this->databasePrice->updatePriceCategory($entry);
        }

        // Очищает cache.
        Cache::invalidateTags(['price']);        

        $response = new AjaxResponse();
        
        $message = '';
        if ($return_created) {
            $message = $this->t('Category <strong>@title</strong> created.', array('@title' => $price_category->title)); 
            $response->addCommand(new HtmlCommand('#site-price-category-message', $message));
            $response->addCommand(new InvokeCommand('.form-text', 'val', array('')));
            $response->addCommand(new InvokeCommand('#edit-cost', 'val', array('0.00')));
            $response->addCommand(new InvokeCommand('#edit-cost-discount', 'val', array('0.00')));
        }
        if ($return_updated) {
            $message = $this->t('Category updated.');  
            $response->addCommand(new BeforeCommand('#price__category-' . $price_category->cid, '<i class="fa fa-floppy-o" aria-hidden="true"></i> ' . $message));            
            $response->addCommand(new CssCommand('#price__category-' . $price_category->cid, array('border' => '2px solid #fcefa1')));
            $response->addCommand(new CloseModalDialogCommand());
        }        

        return $response;
    }
}
