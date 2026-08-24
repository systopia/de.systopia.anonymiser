<?php

declare(strict_types = 1);

use CRM_Anonymiser_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class api_v3_Contact_AnonymiseTest extends \CivixPhar\PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    HookInterface,
    TransactionalInterface {

  use Civi\Test\Api3TestTrait;

  /**
   * @return \Civi\Test\CiviEnvBuilder
   */
  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Example: Test that a version is returned.
   *
   * @return void
   */
  public function testAnonymiseContactWithChildActivity() {
    $contact = $this->callAPISuccess('Contact', 'create', [
      'first_name' => 'Roger',
      'last_name' => 'Rabbit',
      'contact_type' => 'Individual',
    ]);
    self::assertIsArray($contact);
    $parentActivity = $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $contact['id'],
      'target_contact_id' => $contact['id'],
      'activity_type_id' => 'Meeting',
      'subject' => 'Eat Carrot',
    ]);
    self::assertIsArray($parentActivity);
    $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $contact['id'],
      'activity_type_id' => 'Meeting',
      'subject' => 'Nibble the chewy bits first',
      'parent_id' => $parentActivity['id'],
    ]);
    $this->callAPISuccess('Contact', 'anonymise', ['contact_id' => $contact['id']]);
  }

  /**
   * Example: Test that a version is returned.
   *
   * @return void
   */
  public function testAnonmyseContactWithLogging() {
    $this->callAPISuccess('Setting', 'create', ['logging' => 0]);
    $this->callAPISuccess('Setting', 'create', ['logging' => 1]);
    $placebo = $this->callAPISuccess('Contact', 'create', [
      'first_name' => 'Roger',
      'last_name' => 'Rabbit',
      'contact_type' => 'Individual',
      'api.Contribution.create' => [
        'financial_type_id' => 'Donation',
        'total_amount' => 4,
        'date_received' => 'now',
      ],
    ]);

    $contact = $this->callAPISuccess('Contact', 'create', [
      'first_name' => 'Wodger',
      'last_name' => 'Rabbit',
      'contact_type' => 'Individual',
      'api.Contribution.create' => [
        'financial_type_id' => 'Donation',
        'total_amount' => 4,
        'date_received' => 'now',
      ],
    ]);
    self::assertIsArray($contact);
    $result = $this->callAPISuccess('Contact', 'anonymise', ['contact_id' => $contact['id']]);
    self::assertIsArray($result);
    self::assertTrue(in_array(
      'Removed entries for 1 LineItem(s) from logging table \'log_civicrm_line_item\'.',
      $result['values'],
      TRUE
    ));

    $this->callAPISuccess('Setting', 'create', ['logging' => 0]);
  }

  /**
   * Implements hook_alterLogTables().
   *
   * @param array<string, array<string, mixed>> $logTableSpec
   */
  // phpcs:ignore Drupal.Commenting.HookComment.HookParamDoc
  public function hook_civicrm_alterLogTables(array &$logTableSpec): void {
    foreach (array_keys($logTableSpec) as $tableName) {
      $logTableSpec[$tableName]['engine'] = 'INNODB';
      $logTableSpec[$tableName]['engine_config'] = 'ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=4';
    }
  }

}
