<?php
/*-------------------------------------------------------+
| SYSTOPIA Anonymiser                                    |
| Copyright (C) 2016-2021 SYSTOPIA                       |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

require_once 'anonymiser.civix.php';

use CRM_Anonymiser_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function anonymiser_civicrm_config(&$config) {
  _anonymiser_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function anonymiser_civicrm_xmlMenu(&$files) {
  _anonymiser_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function anonymiser_civicrm_install() {
  _anonymiser_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function anonymiser_civicrm_uninstall() {
  _anonymiser_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function anonymiser_civicrm_enable() {
  _anonymiser_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function anonymiser_civicrm_disable() {
  _anonymiser_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function anonymiser_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _anonymiser_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function anonymiser_civicrm_managed(&$entities) {
  _anonymiser_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function anonymiser_civicrm_caseTypes(&$caseTypes) {
  _anonymiser_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function anonymiser_civicrm_angularModules(&$angularModules) {
_anonymiser_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function anonymiser_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _anonymiser_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * add an action for the contact
 */
function anonymiser_civicrm_summaryActions( &$actions, $contactID ) {
  $actions['contact_anonymise'] = array(
      'title'           => ts("Anonymise Contact", array('domain' => 'de.systopia.anonymiser')),
      'weight'          => 5,
      'ref'             => 'contact-anonymise',
      'key'             => 'contact_anonymise',
      'class'           => 'crm-popup small-popup',
      'href'            => CRM_Utils_System::url('civicrm/contact/anonymise', "cid=$contactID"),
      'permissions'     => array('administer CiviCRM')
    );
}

/**
 * add anonymisaion runner for search result
 */
function anonymiser_civicrm_searchTasks($objectType, &$tasks)
{
  // add "anonymise" task to contact search action
  if ($objectType == 'contact') {
    $tasks[] = [
        'title'       => E::ts('Anonymise'),
        'class'       => 'CRM_Anonymiser_Form_Task_Anonymise',
        'result'      => false,
        'permissions' => ['administer CiviCRM'],
    ];
  }
}

/**
 * Set permission to the API calls
 */
function anonymiser_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  $permissions['contact']['anonymise'] = array('administer CiviCRM');
}

/**
 * Implements hook_civicrm_container().
 *
 * @param Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function anonymiser_civicrm_container(Symfony\Component\DependencyInjection\ContainerBuilder $container) {
  if (class_exists('Civi\Anonymiser\CompilerPass')) {
    $container->addCompilerPass(new Civi\Anonymiser\CompilerPass());
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function anonymiser_civicrm_navigationMenu(&$menu) {
  _anonymiser_civix_insert_navigation_menu($menu, 'Administer', [
    'label' => E::ts('Anonymiser Settings'),
    'name' => 'anonymiser',
    'permission' => 'administer CiviCRM',
    'child' => [],
    'operator' => 'AND',
    'separator' => 0,
    'url' => CRM_Utils_System::url('civicrm/admin/setting/anonymiser', 'reset=1', TRUE),
  ]);
  _anonymiser_civix_navigationMenu($menu);
}
