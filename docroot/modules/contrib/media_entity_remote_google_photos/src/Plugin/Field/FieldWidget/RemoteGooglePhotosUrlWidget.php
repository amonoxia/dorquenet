<?php

namespace Drupal\media_entity_remote_google_photos\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * remote Google photos URL field widget.
 *
 * @FieldWidget(
 *   id = "rgp_url_widget",
 *   label = @Translation("remote Google photos Url"),
 *   description = @Translation("A plaintext field for a remote Google photos url plus fields for metadata."),
 *   field_types = {
 *     "rgp_url"
 *   }
 * )
 */
class RemoteGooglePhotosUrlWidget extends LinkWidget {

  /**
   * @inheritDoc
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    unset($element['title']);
    unset($element['attributes']);

    return $element;
  }

}
