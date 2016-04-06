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
 * This worker class will perform the actual anonymisation process
 */
class CRM_Anonymiser_Worker {

  /** store a configuration object for performance reasons */
  protected $config = NULL;

  public function __construct() {
    $this->config = new CRM_Anonymiser_Configuration();
  }

  /**
   * Perform the anonymisation process on the given contact
   * CAUTION: This is irreversible
   * 
   * @param $contact_id int   ID of the contact
   */
  public static function anonymise_contact($contact_id) {
    $worker = new CRM_Anonymiser_Worker();
    return $worker->anonymiseContact($contact_id);
  }



  /**
   * Perform the anonymisation process on the given contact
   * CAUTION: This is irreversible
   * 
   * @param $contact_id int   ID of the contact
   */
  public function anonymiseContact($contact_id) {
    $contact_id = (int) $contact_id;
    if (empty($contact_id)) throw new Exception(ts("No contact ID given!"));

    // first of all: check if everything's in place
    $this->config->systemCheck();

    // clean out the basic data
    $this->anoymiseBaseData($contact_id);

    // delete attached entries
    $entities_to_delete = $this->config->getEntitiesToDelete();
    foreach ($entities_to_delete as $entity_name => $entity_spec) {
      $this->deleteRelatedEntity($entity_name, $entity_spec);
    }

    
  }

}
