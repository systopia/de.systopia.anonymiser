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
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function anonymiser_civicrm_install() {
  _anonymiser_civix_civicrm_install();
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
