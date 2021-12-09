<?php

namespace Drupal\media_entity_remote_google_photos\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Plugin implementation of the 'remote_google_photos_url' field type.
 *
 * @FieldType(
 *   id = "rgp_url",
 *   label = @Translation("Google photos image URL"),
 *   description = @Translation("This field is used to the URL of a Google photos image."),
 *   category = @Translation("General"),
 *   default_widget = "rgp_url_widget",
 *   default_formatter = "rgp_url_formatter"
 * )
 */
class RemoteGooglePhotosUrl extends LinkItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    unset($properties['title']);
    unset($properties['options']);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    unset($schema['columns']['title']);
    unset($schema['columns']['options']);

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);

    $element['link_type']['#default_value'] = static::LINK_EXTERNAL;
    $element['link_type']['#disabled'] = true;

    $element['title']['#default_value'] = DRUPAL_DISABLED;
    $element['title']['#disabled'] = true;

    return $element;
  }

}
