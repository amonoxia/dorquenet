<?php

namespace Drupal\media_entity_remote_google_photos\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the GooglePhotosImageUrl constraint.
 */
class GooglePhotosImageUrlConstraintValidator extends ConstraintValidator
{

  const GOOGLE_PHOTOS_HOST = 'googleusercontent.com';

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint)
  {

    $value = $value->getValue();
    $value = reset($value);
    $parsedUrl = parse_url($value['uri']);

    if (strpos($parsedUrl['host'], self::GOOGLE_PHOTOS_HOST)  === false) {
      $this->context->buildViolation($constraint->message)
        ->atPath('uri')
        ->addViolation();
    }
  }

}
