<?php

namespace Drupal\synonyms_autocomplete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\synonyms\ProviderInterface\FormatWordingTrait;

/**
 * Synonyms autocomplete widget settings form.
 */
class SettingsForm extends ConfigFormBase {

  use StringTranslationTrait, FormatWordingTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'synonyms_autocomplete_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['synonyms_autocomplete.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $description = $this->t('Specify the wording with which the autocomplete widget suggestions should be presented. Available replacement tokens are: @replacements This will also serve as a fallback wording if more specific wordings are left empty.', [
      '@replacements' => $this->formatWordingAvailableTokens(),
    ]);

    // The 'Default wording' textfield.
    $form['default_wording'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wording'),
      '#default_value' => $this->config('synonyms_autocomplete.settings')->get('default_wording'),
      '#description' => $description,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('synonyms_autocomplete.settings')
      ->set('default_wording', $form_state->getValue('default_wording'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
