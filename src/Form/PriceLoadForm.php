<?php

/**
 * @file
 * Contains \Drupal\site_price\Form\PriceLoadForm
 */

namespace Drupal\site_price\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_price\Controller\PriceDatabaseController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Форма выбора прайс-оиста для загрузки.
 */
class PriceLoadForm extends FormBase {

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
        return 'site_price_load_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $prid = 0) {
        // Загружаем объекты если передан идентификатор.
        if (!$prid) {
           $prid = \Drupal::state()->get('site_price.last_price_load', 0);
        }        
        $price = $this->databasePrice->loadPrice($prid);

        // Проверяет наличие созданных прайс-листов.
        $prices = $this->databasePrice->loadPriceLists();
        if (!count($prices)) {
            drupal_set_message(t('Create your first price list in the form below.'), 'status');
            return $this->redirect('site_price.create_price');
        }

        $form['prid'] = array(
            '#title' => $this->t('Price list'),
            '#type' => 'select',
            '#default_value' => isset($price->prid) ? $price->prid : 0,
            '#options' => $prices,
        );

        // Добавляем элемент где будем выводить системные сообщения.
        $form['system_messages'] = [
            '#markup' => '<div id="form-system-messages"></div>',
        ];

        // Now we add our submit button, for submitting the form results.
        // The 'actions' wrapper used here isn't strictly necessary for tabledrag,
        // but is included as a Form API recommended practice.
        $form['actions'] = array('#type' => 'actions');
        $form['actions']['load'] = [
            '#type' => 'submit',
            '#value' => $this->t('Load'),
            '#ajax' => [
                'callback' => '::ajaxCallbackLoadPriceList',
                'event' => 'click',
                'progress' => [
                    'type' => 'throbber',
                ],
            ],
            '#button_type' => 'primary',
        ];

        $form['actions']['edit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Edit'),
            '#submit' => array('::submitEditPriceList'),
            '#button_type' => 'primary',
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitEditPriceList(array &$form, FormStateInterface $form_state) {
        $form_state->setRedirect('site_price.edit_price', array('prid' => $form_state->getValue('prid')));
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
    public function ajaxCallbackLoadPriceList(array &$form, FormStateInterface $form_state) {
        $prid = $form_state->getValue('prid');

        $ajax_response = new AjaxResponse();

        // Загружает прайс-лист по идентификатору.
        $renderData = array(
            '#theme' => 'site_price',
            '#prid' => $prid,
            '#edit' => TRUE,
        );
        $html = \Drupal::service('renderer')->render($renderData, FALSE);
        $ajax_response->addCommand(new HtmlCommand('.price-admin-page', $html));

        // Сообщение о загруженном типе прайс-листа.
        $message = '<div>' . $this->t('To display the search form, use the call <strong>@function</strong> function in your template.', array('@function' => "{{ getPriceFilter($prid, true) }}")) . '</div>';
        $message .= '<div>' . $this->t('To display the current price list, use the call <strong>@function</strong> function in your template.', array('@function' => "{{ getPrice($prid, true) }}")) . '</div>';
        $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $message));

        return $ajax_response;
    }
}
