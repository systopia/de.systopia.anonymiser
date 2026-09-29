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

declare(strict_types = 1);

/**
 * Allowed @params array keys are:
 *
 * @example SepaCreditorCreate.php Standard Create Example
 *
 * @param array<string, mixed> $params
 *
 * @return array<string, mixed> API result array
 *   {@getfields entity_batch_create}
 * @access public
 */
function civicrm_api3_contact_anonymise($params) {
  if (!is_numeric($params['contact_id'] ?? NULL)) {
    throw new RuntimeException('Missing or invalid contact_id.');
  }
  $worker = new CRM_Anonymiser_Worker();
  $worker->anonymiseContact((int) $params['contact_id']);
  return civicrm_api3_create_success($worker->getLog());
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array<string, mixed> $params array or parameters determined by getfields
 *
 * @return void
 */
function _civicrm_api3_contact_anonymise_spec(&$params) {
  if (!isset($params['contact_id']) || !is_array($params['contact_id'])) {
    $params['contact_id'] = [];
  }
  $params['contact_id']['api.required'] = 1;
}
