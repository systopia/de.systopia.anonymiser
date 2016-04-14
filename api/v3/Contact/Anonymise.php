<?php
/*-------------------------------------------------------+
| SYSTOPIA Contact Anonymiser Extension                  |
| Copyright (C) 2016 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * Allowed @params array keys are:
 *
 * @example SepaCreditorCreate.php Standard Create Example
 *
 * @return array API result array
 * {@getfields entity_batch_create}
 * @access public
 */
function civicrm_api3_contact_anonymise($params) {
  $worker = new CRM_Anonymiser_Worker();
  $worker->anonymiseContact($params['contact_id']);
  return civicrm_api3_create_success($worker->getLog());
}

/**
 * Adjust Metadata for Create action
 * 
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_contact_anonymise_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
}
