<?php

namespace Drupal\media_entity_remote_google_photos\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * remote Google photos URL field formatter.
 *
 * @FieldFormatter(
 *   id = "rgp_url_formatter",
 *   label = @Translation("Google Photos Raw "),
 *   description = @Translation("Display Google Photos image exactly as what it is"),
 *   field_types = {
 *     "rgp_url"
 *   }
 * )
 */
class RemoteGooglePhotosUrlFormatter extends LinkFormatter {

  /**
   * @inheritDoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();

    foreach ($elements as $delta => $entity) {
      $elements[$delta] = [
        '#markup' => Markup::create(
          "<div class='remote-google-photos-raw' style=\"border-radius: 8px;\">
                    <img src='{$values[$delta]['uri']}'>
                  </div>"),
      ];
    }

    return $elements;
  }

}
