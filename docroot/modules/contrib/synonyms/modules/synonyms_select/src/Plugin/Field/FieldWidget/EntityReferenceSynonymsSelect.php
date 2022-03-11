<?php

namespace Drupal\synonyms_select\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'synonyms friendly select' widget.
 *
 * @FieldWidget(
 *   id = "synonyms_select",
 *   label = @Translation("Synonyms-friendly select"),
 *   description = @Translation("A dropdown with entities and their synonyms."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceSynonymsSelect extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $key_column = $this->fieldDefinition->getFieldStorageDefinition()->getPropertyNames()[0];
    $handler_settings = $this->fieldDefinition->getSetting('handler_settings') ?: [];
    $target_bundles = isset($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : NULL;
    $element += [
      '#type' => 'synonyms_entity_select',
      '#key_column' => $key_column,
      '#target_type' => $this->getFieldSetting('target_type'),
      '#target_bundles' => $target_bundles,
      '#multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
      '#default_value' => $this->getSelectedOptions($items),
      '#empty_value' => '_none',
      '#empty_option' => '- None -',
    ];
    return $element;
  }

}
