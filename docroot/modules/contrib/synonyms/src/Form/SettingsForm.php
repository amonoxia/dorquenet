<?php

namespace Drupal\synonyms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a form that configures forms module settings.
 */
class SettingsForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * All available synonyms widgets.
   *
   * @var array
   */
  protected $widgets;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'synonyms_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['synonyms.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The 'Wording type' select item.
    $form['wording_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Wording type'),
      '#options' => $this->wordingTypeOptions(),
      '#default_value' => $this->config('synonyms.settings')->get('wording_type'),
      '#description' => $this->t('<strong>No wording:</strong> All synonyms suggestions inside all synonyms friendly widgets will be presented to the user with synonym labels only.<br><strong>Default wording:</strong> Provides one default (and customisable) wording per widget. Good enough for sites with simple synonyms usage.<br><strong>Per entity type:</strong> Enables per entity type specific wording for each widget at "Manage behaviors" form.<br><strong>Per entity type and field:</strong> Enables per field (provider) specific wording at "Manage providers" form. One wording is used by all widgets here.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('synonyms.settings')
      ->set('wording_type', $form_state->getValue('wording_type'))
      ->set('wording_type_label', $this->wordingTypeOptions()[$form_state->getValue('wording_type')])
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function defining wording type options.
   */
  protected function wordingTypeOptions() {
    $options = [
      'none' => $this->t('No wording'),
      'default' => $this->t('Default wording'),
      'entity' => $this->t('Per entity type'),
      'provider' => $this->t('Per entity type and field'),
    ];
    return $options;
  }

}
