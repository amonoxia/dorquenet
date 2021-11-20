<?php

namespace Drupal\Tests\spambot\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Test spambot report account.
 *
 * @group spambot
 */
class SpambotUserspamTest extends KernelTestBase implements ServiceModifierInterface {

  /**
   * User for tests.
   *
   * @var \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'spambot',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create an user that we will use for testing.
    $this->user = User::create(['name' => 'amparohm69', 'mail' => 'nelda@singaporetravel.network']);
  }

  /**
   * Test spambot_report_account() function without "api_key".
   */
  public function testSpambotReportAccountEmptyApiKey() {
    $key = '';
    $success = spambot_report_account($this->user, '130.176.13.140', 'title', $key);
    $this->assertFalse($success, 'Field api key should not be filled.');
  }

  /**
   * Test spambot_report_account() function with incorrect "api_key".
   */
  public function testSpambotReportAccountIncorrectApiKey() {
    $key = 'notExist503';
    $success = spambot_report_account($this->user, '130.176.13.140', 'title', $key);
    $this->assertFalse($success, 'Field api key should not be filled.');
  }

  /**
   * Test spambot_report_account() function with correct "api_key".
   */
  public function testSpambotReportSpamAccount() {
    $key = 'wqd31vfkhae9gn';
    $success = spambot_report_account($this->user, '130.176.13.140', 'title', $key);
    $this->assertTrue($success, 'Guzzle exception.');
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->removeDefinition('test.http_client.middleware');
  }

}
