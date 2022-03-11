<?php

namespace Drupal\synonyms\ProviderInterface;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Component\Utility\Html;
use Drupal\synonyms\SynonymInterface;

/**
 * Trait to format wording of a synonym.
 */
trait FormatWordingTrait {

  /**
   * {@inheritdoc}
   */
  public function synonymFormatWording($synonym, ContentEntityInterface $entity, SynonymInterface $synonym_config, $service_id) {
    // @todo Maybe we should use tokens replacement here? But then it would mean
    // an extra dependency on the tokens module. Is it worth it? For now let's
    // use stupid str_replace() and incorporate tokens only if user base really
    // asks for it.
    $wording_type = \Drupal::config('synonyms.settings')->get('wording_type');

    // If the wording type is 'No wording' it's simple.
    if ($wording_type == 'none') {
      return $synonym;
    }
    // If not... we have some job to do.
    else {
      $wording = '';
      $plugin_definition = $synonym_config->getProviderPluginInstance()->getPluginDefinition();
      $entity_type = $plugin_definition['controlled_entity_type'];
      $bundle = $plugin_definition['controlled_bundle'];
      // Try widget's wording per entity type and provider.
      if ($wording_type == 'provider') {
        $provider_configuration = $synonym_config->getProviderConfiguration();
        if (isset($provider_configuration['wording'])) {
          $get_wording = $provider_configuration['wording'];
        }
        $wording = !empty($get_wording) ? $get_wording : $wording;
      }
      // Try widget's wording per entity type.
      if (empty($wording) && ($wording_type == 'provider' || $wording_type == 'entity')) {
        $get_wording = \Drupal::config('synonyms_' . $service_id . '.behavior.' . $entity_type . '.' . $bundle)->get('wording');
        $wording = !empty($get_wording) ? $get_wording : $wording;
      }
      // Try default widget's wording and if it is empty as well
      // fallback to basic '@synonym' wording.
      if (empty($wording) && ($wording_type == 'provider' || $wording_type == 'entity' || $wording_type == 'default')) {
        $get_wording = \Drupal::config('synonyms_' . $service_id . '.settings')->get('default_wording');
        $wording = !empty($get_wording) ? $get_wording : $wording;
      }
      // Ultimate fallback if all other wordings are empty.
      if (empty($wording)) {
        $wording = '@synonym';
      }

      $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
      $field_label = $definitions[$plugin_definition['field']]->getLabel();
      $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
      $bundle_label = $bundle_info[$bundle]['label'];

      $map = [
        '@synonym' => $synonym,
        '@entity_label' => $entity->label(),
        '@Field_label' => $field_label,
        '@field_label' => strtolower($field_label),
        '@FIELD_LABEL' => strtoupper($field_label),
        '@Bundle' => $bundle_label,
        '@bundle' => strtolower($bundle_label),
        '@BUNDLE' => strtoupper($bundle_label),
      ];

      return str_replace(array_keys($map), array_values($map), $wording);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatWordingAvailableTokens() {
    // The array of supported tokens in wording. Keys are the tokens whereas
    // corresponding values are explanations about what each token means.
    $tokens = [
      '@synonym' => $this->t('The synonym value'),
      '@entity_label' => $this->t('The label of the entity this synonym belongs to'),
      '@Field_label' => $this->t('The label of the provider field'),
      '@field_label' => $this->t('The lowercase label of the provider field'),
      '@FIELD_LABEL' => $this->t('The uppercase label of the provider field'),
      '@Bundle' => $this->t('The label of the entity bundle'),
      '@bundle' => $this->t('The lowercase label of the entity bundle'),
      '@BUNDLE' => $this->t('The uppercase label of the entity bundle'),
    ];

    $replacements = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [],
    ];
    foreach ($tokens as $token => $token_info) {
      $replacements['#items'][] = Html::escape($token) . ': ' . $token_info;
    }

    return \Drupal::service('renderer')->renderRoot($replacements);
  }

}
