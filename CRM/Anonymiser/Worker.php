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

  /** store a log file of what happened */
  protected $log = array();

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
    $this->anoymiseContactBase($contact_id);

    // delete attached entries
    $entities_to_delete = $this->config->getEntitiesToDelete();
    foreach ($entities_to_delete as $entity_name => $entity_spec) {
      $this->deleteRelatedEntity($entity_name, $contact_id, $entity_spec);
    }

    // delete relations

    // delete groups/tags

    // anonymise memberships

    // anonymise events

    // anonymise contributions

    // clean LOGGING tables

  }




  /**
   * Anonymise the contact base
   */
  protected function anoymiseContactBase($contact_id) {
    // first: load the contact
    $contact = civicrm_api3('Contact', 'get', array('id' => $contact_id));

    // then: get all fields to overwrite
    $fields = $this->config->getOverrideFields($Contact);
    $erase_query = array('id' => $contact_id);
    foreach ($fields as $field_name => $anon_type) {
      $erase_query[$field_name] = $this->config->generateAnonymousValue($field_name, $anon_type);
    }

    error_log(print_r($query,1));
    exit();

  }

  /**
   * Delete an entity that is related to the contact
   * @param $entity_name string the name of the entity as used by the API
   * @param $entity_spec array  parameters used for identification
   */
  protected function deleteRelatedEntity($entity_name, $contact_id, $entity_spec = array()) {
    // first: find all entities
    if (!empty($entity_spec['has_entity_relation'])) {
      $query = array(
        'entity_table' => 'civicrm_contact',
        'entity_id'    => $contact_id,
      );
    } else {
      $query = array(
        'contact_id'   => $contact_id,
        );
    }

    $query['limit.option'] = 999999;
    $query['return'] = 'id';
    $entities_found = civicrm_api3($entity_name, 'get', $query);

    // delete them all
    $count = 0;
    foreach ($entities_found['values'] as $key => $entity) {
      civicrm_api3($entity_name, 'delete', $entity);
      $count++;
    }

    // log this
    $this->log(ts("%1 %2s deleted.", array(1 => $count, 2 => $entity_name, 'domain' => 'de.systopia.analyser')));
  }




  /**
   * log messages during execution
   */
  public function log($message) {
    $this->log[] = $message;
  }

  /**
   * get all log messages
   */
  public function getLog() {
    return $this->log;
  }
}
