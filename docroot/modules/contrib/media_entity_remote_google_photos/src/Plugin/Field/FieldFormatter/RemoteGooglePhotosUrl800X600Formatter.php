<?php

namespace Drupal\media_entity_remote_google_photos\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * remote Google photos URL field formatter.
 *
 * @FieldFormatter(
 *   id = "rgp_url_800x600_formatter",
 *   label = @Translation("Remote Google Photos 800x600"),
 *   description = @Translation("Display the remote Google photos in wide size (800x600)"),
 *   field_types = {
 *     "rgp_url"
 *   }
 * )
 */
class RemoteGooglePhotosUrl800X600Formatter extends LinkFormatter
{

//  /**
//   * {@inheritdoc}
//   */
//  public function viewElements(FieldItemListInterface $items, $langcode) {
//    $elements = parent::viewElements($items, $langcode);
//    $values = $items->getValue();
//
//
//    /** @var \Drupal\media\MediaInterface[] $media_items */
//    foreach ($values as $delta => $media) {
//      $elements[$delta] = [
//        '#theme' => 'image_formatter',
//        '#item_attributes' => [],
//        '#image_style' => $this->getSetting('image_style'),
//        '#url' => $media[$delta]['uri'],
//      ];
//    }
//
//    return $elements;
//  }
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $width = '800';
    $height = '600';
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();
    foreach ($elements as $delta => $entity) {
      $googlePhotoUrl= $values[$delta]['uri'];
      preg_match('/(.*)=w/', $googlePhotoUrl , $matches);
      if($matches && isset($matches[1])){
        $googlePhotoUrl = $matches[1] ."=w$width-h$height-c";
      }else {
        preg_match('/(.*)=s/', $googlePhotoUrl , $matches);
        if ($matches && isset($matches[1])) {
          $googlePhotoUrl = $matches[1] . "=w$width-h$height-c";
        }
      }

      $elements[$delta] = [
        '#markup' => Markup::create(
          "<div class='remote-google-photos-thumbnail'
                    style=\"width: " . $width . "px;height: " . $height . "px; border-radius: 8px;overflow: hidden;\">
                    <img src='{$googlePhotoUrl}' style=\"width: " . $width . "px;height: auto\">
                  </div>"),
      ];
    }

    return $elements;
  }
}
