<?php

namespace Drupal\spambot\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;

/**
 * Returns a render-able array for a spam page.
 */
class SpambotUserSpamPageController extends ControllerBase {

  /**
   * Returns a render-able array for a spam page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user who can be reported.
   *
   * @return array
   *   Return form.
   */
  public function spambotUserSpam(UserInterface $user) {
    $myForm = $this->formBuilder()->getForm('Drupal\spambot\Form\SpambotUserspamForm', $user);
    $renderer = \Drupal::service('renderer');
    $myFormHtml = $renderer->render($myForm);

    return [
      '#markup' => $myFormHtml,
    ];
  }

}
