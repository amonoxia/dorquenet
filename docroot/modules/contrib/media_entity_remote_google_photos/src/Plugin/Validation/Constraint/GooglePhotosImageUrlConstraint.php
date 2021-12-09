<?php

namespace Drupal\media_entity_remote_google_photos\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check if a value is a valid URL.
 *
 * @constraint(
 *   id = "GooglePhotosImageUrl",
 *   label = @Translation(" Google photos image Url", context = "Validation"),
 *   type = { "rgp_url"  }
 * )
 */
class GooglePhotosImageUrlConstraint extends Constraint
{

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'This is not a valid Google Photos image URL.
            <br>To get valid URL right click on the Google Photos image and choose "copy image address" ';

}
