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

    // delete entities, that cannot be (sensibly) anonymised
    $entities_to_delete = $this->config->getEntitiesToDelete();
    foreach ($entities_to_delete as $entity_name) {
      $this->deleteRelatedEntities($entity_name, $contact_id);
    }

    // ANONYMISE memberships
    if (!$this->config->deleteMemberships()) {
      $this->anonymiseMemberships($contact_id);
    }

    // ANONYMISE participants
    if (!$this->config->deleteEvents()) {
      $this->anonymiseParticipants($contact_id);
    }

    // ANONYMISE contributions
    if (!$this->config->deleteContributions()) {
      $this->anonymiseContributions($contact_id);
    }

    // clean LOGGING tables
    if ($this->config->deleteLogs()) {
      $affected_tables = $this->getAffectedTables();
      foreach ($affected_log as $table_name) {
        $log_table_name = $this->config->getLogTableForTable($table_name);

        $sql_indetifiers = 

        if ($this->config->isEntityRelationScheme($table_name)) {
          CRM_Core_DAO::executeQuery("DELETE FROM `$log_table_name` WHERE ")
        }
        # code...
      }
    }
  }




  /**
   * Anonymise the contact base
   */
  protected function anoymiseContactBase($contact_id) {
    // first: load the contact
    $contact = civicrm_api3('Contact', 'get', array('id' => $contact_id));

    // then: get all fields to overwrite
    $fields = $this->config->getOverrideFields('Contact', $contact);
    $erase_query = array('id' => $contact_id);
    foreach ($fields as $field_name => $anon_type) {
      $erase_query[$field_name] = $this->config->generateAnonymousValue($field_name, $anon_type);
    }

    civicrm_api3('Contact', 'create', $erase_query);

    // TODO: anything else?
  }

  /**
   * Delete an entity that is related to the contact
   * @param $entity_name string the name of the entity as used by the API
   * @param $entity_spec array  parameters used for identification
   */
  protected function deleteRelatedEntities($entity_name, $contact_id) {
    // TODO: Exception for Note
    // TODO: Exception for Activity

    // first: find all entities
    if ($this->config->isEntityRelationScheme($entity_name)) {
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
