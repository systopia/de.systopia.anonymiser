<?php

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
class api_v3_Contact_AnonymiseTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface {

  use Civi\Test\Api3TestTrait;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Example: Test that a version is returned.
   */
  public function testAnonmyseContactWithChildActivity() {
    $contact = $this->callApiSuccess('Contact', 'create', [
      'first_name' => 'Roger',
      'last_name' => 'Rabbit',
      'contact_type' => 'Individual',
    ]);
    $parentActivity = $this->callApiSuccess('Activity', 'create', [
      'source_contact_id' => $contact['id'],
      'activity_type_id' => 'Meeting',
      'subject' => 'Eat Carrot',
    ]);
    $childActivity = $this->callApiSuccess('Activity', 'create', [
      'source_contact_id' => $contact['id'],
      'activity_type_id' => 'Meeting',
      'subject' => 'Nibble the chewy bits first',
      'parent_id' => $parentActivity['id'],
    ]);
    $this->callApiSuccess('Contact', 'anonymise', ['contact_id' => $contact['id']]);

  }

}
