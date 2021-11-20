<?php

namespace Drupal\Tests\spambot\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test of Spambot caching functionality.
 *
 * @group spambot
 */
class SpambotCacheTest extends KernelTestBase implements ServiceModifierInterface {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = ['spambot'];

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installConfig(['spambot']);
  }

  /**
   * Tests spambot caching functionality.
   */
  public function testSpambotCaching() {
    $username = mb_strtolower($this->getRandomGenerator()->name());
    $email = mb_strtolower($this->getRandomGenerator()->name()) . '@example.com';
    $ip = '' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255);

    $mock_query = [
      'username' => $username,
      'email' => $email,
      'ip' => $ip,
    ];
    $mock_data = [];

    spambot_sfs_request($mock_query, $mock_data);

    $cache_username = \Drupal::cache('spambot')->get("username:{$username}");
    $cache_email = \Drupal::cache('spambot')->get("email:{$email}");
    $cache_ip = \Drupal::cache('spambot')->get("ip:{$ip}");

    $this->assertNotFalse($cache_username);
    $this->assertNotFalse($cache_email);
    $this->assertNotFalse($cache_ip);

  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->removeDefinition('test.http_client.middleware');
  }

}
