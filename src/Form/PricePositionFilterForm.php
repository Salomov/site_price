<?php

/**
 * @file
 * Contains Drupal\site_price\Form\PricePositionFilterForm
 */

namespace Drupal\site_price\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\site_price\Ajax\SitePriceScrollToCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class PricePositionFilterForm extends FormBase {

    public function getFormId() {
        return 'site_price_position_search_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $prid = 0, $edit = FALSE) {

        $form['prid'] = array(
            '#type' => 'value',
            '#value' => $prid,
        );
        $form['edit'] = array(
            '#type' => 'value',
            '#value' => $edit,
        );

        // Строка поиска позиций.
        $form['search_text_field'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Quick search'),
            '#title_display' => 'invisible',
            '#attributes' => array('placeholder' => $this->t('Write what you want to find'), 'class' => array('site-price-position-search-form__text-field')),
            '#weight' => 1,
            '#maxlength' => 255,
        ];

        // Now we add our submit button, for submitting the form results.
        // The 'actions' wrapper used here isn't strictly necessary for tabledrag,
        // but is included as a Form API recommended practice.
        $form['actions'] = array('#type' => 'actions', '#weight' => 2,);
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Find'),
            '#ajax' => [
                'callback' => '::ajaxTextfieldChangeCallback',
                'progress' => array(),
            ],
            '#button_type' => 'primary',
            '#attributes' => array('class' => array('site-price-position-search-form__btn site-price-position-search-form__btn_find')),
        ];
        $form['actions']['clear'] = [
            '#type' => 'submit',
            '#value' => $this->t('Show all'),
            '#ajax' => [
                'callback' => '::ajaxTextfieldShowAllCallback',
                'progress' => array(),
            ],
            '#attributes' => array('class' => array('site-price-position-search-form__btn site-price-position-search-form__btn_clear hidden')),
        ];

        // Добавляем элемент где будем выводить системные сообщения.
        $form['system_message'] = [
            '#markup' => '<div id="site-price-position-search-form__message"></div>',
            '#weight' => 3,
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
    public function ajaxTextfieldChangeCallback(array &$form, FormStateInterface $form_state) {
        /** @var \Drupal\Core\Render\RendererInterface $renderer */
        $renderer = \Drupal::service('renderer');

        // Текст для поиска-фильтрации.
        $search_text_field = trim($form_state->getValue('search_text_field'));

        $response = new AjaxResponse();

        if ($search_text_field) {
            $renderData = array(
                '#theme' => 'site_price',
                '#prid' => $form_state->getValue('prid'),
                '#edit' => $form_state->getValue('edit'),
                '#filter' => $search_text_field,
            );
            $price = $renderer->render($renderData);

            $response->addCommand(new HtmlCommand('#site-price-position-search-form__message', $this->t('Results search: @text', array('@text' => $search_text_field))));
            $response->addCommand(new InvokeCommand('#site-price-position-search-form__message', 'removeClass', array('site-price-position-search-form__message_warning')));
            $response->addCommand(new InvokeCommand('#site-price-position-search-form__message', 'addClass', array('site-price-position-search-form__message_active')));
            $response->addCommand(new HtmlCommand('.price', $price));
            $response->addCommand(new InvokeCommand('#edit-search-text-field', 'val', array('')));
            $response->addCommand(new InvokeCommand('.site-price-position-search-form__btn_clear', 'removeClass', array('hidden')));
            $response->addCommand(new CssCommand('.site-price-position-search-form__btn_clear', array('display' => 'inline-block', 'visibility' => 'visible')));
        } else {
            $response->addCommand(new HtmlCommand('#site-price-position-search-form__message', $this->t('Type in the search box the first letter of your request and then click «Find».')));
            $response->addCommand(new InvokeCommand('#site-price-position-search-form__message', 'addClass', array('site-price-position-search-form__message_warning')));
        }

        $response->addCommand(new SitePriceScrollToCommand(array('object' => '.site-price-position-search-form__search-block', 'duration' => 100)));

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function ajaxTextfieldShowAllCallback(array &$form, FormStateInterface $form_state) {
        /** @var \Drupal\Core\Render\RendererInterface $renderer */
        $renderer = \Drupal::service('renderer');

        $response = new AjaxResponse();

        // Загружает прайс-лист по идентификатору.
        $renderData = array(
            '#theme' => 'site_price',
            '#prid' => $form_state->getValue('prid'),
            '#edit' => $form_state->getValue('edit'),
            '#filter' => NULL,
        );
        $price = $renderer->render($renderData);

        $response->addCommand(new HtmlCommand('#site-price-position-search-form__message', ''));
        $response->addCommand(new InvokeCommand('#site-price-position-search-form__message', 'removeClass', array('site-price-position-search-form__message_active')));
        $response->addCommand(new InvokeCommand('#site-price-position-search-form__message', 'removeClass', array('site-price-position-search-form__message_warning')));
        $response->addCommand(new HtmlCommand('.price', $price));
        $response->addCommand(new InvokeCommand('#edit-search-text-field', 'val', array('')));
        $response->addCommand(new CssCommand('.site-price-position-search-form__btn_clear', array('display' => 'none')));

        return $response;
    }
}