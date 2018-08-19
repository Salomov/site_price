<?php

/**
 * @file
 * Contains \Drupal\site_price\Form\ConfirmPricePositionDeleteContentForm
 */

namespace Drupal\site_price\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\site_price\Controller\PriceDatabaseController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirm position delete form.
 */
class ConfirmPricePositionDeleteContentForm extends ConfirmFormBase {

    /**
     * The parametrs.
     *
     * @var integral and string
     */
    protected $pid;
    protected $id;
    protected $type;

    /**
     * The object to delete.
     *
     * @var object
     */
    protected $position;
    protected $group;
    protected $category;

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
        return 'price_position_delete_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $pid = 0, $id = 0, $type = '') {
        $this->pid = (int)$pid;
        $this->id = (int)$id;
        $this->type = $type;

        if ($this->pid && $this->id && $this->type) {
            $this->position = $this->databasePrice->loadPricePosition($this->pid);
        }
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion() {
        return $this->t('Are you sure you want to delete «@title» from content?', array('@title' => $this->position->title));
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
        $this->databasePrice->deletePositionFromContent($this->position->pid, $this->id, $this->type);

        // Очищает cache.
        Cache::invalidateTags(['price']);

        drupal_set_message($this->t('Position «@title» deleted from content.', array('@title' => $this->position->title)));
        $form_state->setRedirect('site_price.admin');
    }
}