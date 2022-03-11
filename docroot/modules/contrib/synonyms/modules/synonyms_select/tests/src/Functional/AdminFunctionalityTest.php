<?php

namespace Drupal\Tests\synonyms_select\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Checks if admin functionality works correctly.
 *
 * @group synonyms
 */
class AdminFunctionalityTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'synonyms',
    'synonyms_select',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->container->get('router.builder')->rebuild();

    // Log in an admin user.
    $account = $this->drupalCreateUser([
      'administer site configuration',
      'administer synonyms',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Make sure the main admin page loads correctly.
   *
   * It should contain the default select widget wording.
   */
  public function testSynonymsAdmin() {
    // Load the main admin page.
    $this->drupalGet('admin/structure/synonyms');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains('Synonyms configuration');
    $session->pageTextContains('Default wordings:');
    $session->pageTextContains('Synonyms-friendly select widget: @synonym is the @field_label of @entity_label');

    // Load the Select widget settings page.
    $this->drupalGet('admin/structure/synonyms_select/settings');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains('Synonyms select widget settings');
    $session->pageTextContains('Default wording');
    $session->fieldValueEquals('default_wording', '@synonym is the @field_label of @entity_label');
    $session->pageTextContains('Specify the wording');
    $session->pageTextContains('@field_label: The lowercase label of the provider field');
    $session->pageTextContains('This will also serve as a fallback wording');
    $session->buttonExists('Save configuration');

    // Edit settings.
    $edit = [
      'default_wording' => 'Test wording',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Confirm the change.
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->addressEquals('admin/structure/synonyms_select/settings');
    $session->pageTextContains('Synonyms select widget settings');
    $session->fieldValueEquals('default_wording', 'Test wording');
    $session->buttonExists('Save configuration');

    // Confirm the change at the main admin page.
    $this->drupalGet('admin/structure/synonyms');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains('Synonyms configuration');
    $session->pageTextContains('Default wordings:');
    $session->pageTextContains('Synonyms-friendly select widget: Test wording');

    // Load the Manage behaviors page for User entity type.
    $this->drupalGet('admin/structure/synonyms/behavior/user/user');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains('Manage behaviors of User');
    $session->pageTextContains('Select service');
    $session->checkboxNotChecked('select_status');
    $session->buttonExists('Save configuration');

    // Edit settings.
    $edit = [
      'select_status' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Confirm the change.
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->addressEquals('admin/structure/synonyms/behavior/user/user');
    $session->pageTextContains('Manage behaviors of User');
    $session->pageTextContains('Select service');
    $session->checkboxChecked('select_status');
    $session->buttonExists('Save configuration');
  }

}
