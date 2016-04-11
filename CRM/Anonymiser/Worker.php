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
    if (!$this->config->deleteParticipations()) {
      $this->anonymiseParticipants($contact_id);
    }

    // ANONYMISE contributions
    if (!$this->config->deleteContributions()) {
      $this->anonymiseContributions($contact_id);
    }

    // clean LOGGING tables
    if ($this->config->deleteLogs()) {
      $affected_tables = $this->config->getAffectedTables();
      foreach ($affected_tables as $table_name) {
        $entity_name    = $this->config->getEntityForTable($table_name);
        $log_table_name = $this->config->getLogTableForTable($table_name);
        $identifiers    = $this->config->getIdentifiers($entity_name, $contact_id);
        foreach ($identifiers['sql'] as $where_clause) {
          error_log("DELETE FROM `$log_table_name` WHERE ($where_clause);");
          // $this->log("TODO: DELETE FROM `$log_table_name` WHERE ($where_clause);");
          CRM_Core_DAO::executeQuery("DELETE FROM `$log_table_name` WHERE ($where_clause);");
        }
      }
      $this->log(ts("%1 log tables cleaned.", array(1 => count($affected_tables), 'domain' => 'de.systopia.analyser')));
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
    $deleted_count = 0;

    // first: find all entities
    $identifiers = $this->config->getIdentifiers($entity_name, $contact_id);
    foreach ($identifiers['api'] as $query) {
      $query['limit.option'] = 999999;
      $query['return'] = 'id';
      $entities_found = civicrm_api3($entity_name, 'get', $query);

      // delete them all
      foreach ($entities_found['values'] as $key => $entity) {
        civicrm_api3($entity_name, 'delete', $entity);
        $deleted_count++;
      }
    }

    // log this
    $this->log(ts("%1 %2(s) deleted.", array(1 => $deleted_count, 2 => $entity_name, 'domain' => 'de.systopia.analyser')));
  }


  /**
   * anonymises the contact's membership information,
   * without deleting statistically relevant data
   */
  protected function anonymiseMemberships($contact_id) {
    $memberships = civicrm_api3('Membership', 'get', array('id' => $contact_id, 'option.limit' => 99999));

    // iterate through all memberships
    foreach ($memberships['values'] as $membership) {
      $fields = $this->config->getOverrideFields('Membership', $membership);
      if (!empty($fields)) {
        $update_query = array('id' => $membership['id']);
        foreach ($fields as $field_name => $type) {
          $update_query[$field_name] = $this->config->generateAnonymousValue($field_name, $type, $membership[$field_name]);
        }
        civicrm_api3('Membership', 'create', $update_query);
        $this->log(ts("Anonymised Membership [%1].", array(1 => $membership['id'], 'domain' => 'de.systopia.analyser')));
      } else {
        $this->log(ts("Membership [%1] did not need anonymisation.", array(1 => $membership['id'], 'domain' => 'de.systopia.analyser')));
      }
    }

    if ($memberships['count'] == 0) {
      $this->log(ts("0 Membership entities found for anonymisation.", array('domain' => 'de.systopia.analyser')));
    }
  }

  /**
   * anonymises the contact's event participation information,
   * without deleting statistically relevant data
   */
  protected function anonymiseParticipants($contact_id) {
    $participants = civicrm_api3('Participant', 'get', array('id' => $contact_id, 'option.limit' => 99999));

    // iterate through all participants
    foreach ($participants['values'] as $participant) {
      $fields = $this->config->getOverrideFields('Participant', $participant);
      if (!empty($fields)) {
        $update_query = array('id' => $participant['id']);
        foreach ($fields as $field_name => $type) {
          $update_query[$field_name] = $this->config->generateAnonymousValue($field_name, $type, $participant[$field_name]);
        }
        civicrm_api3('Participant', 'create', $update_query);
        $this->log(ts("Anonymised Participant [%1].", array(1 => $participant['id'], 'domain' => 'de.systopia.analyser')));
      } else {
        $this->log(ts("Participant [%1] did not need anonymisation.", array(1 => $participant['id'], 'domain' => 'de.systopia.analyser')));
      }
    }

    if ($participants['count'] == 0) {
      $this->log(ts("0 Participant entities found for anonymisation.", array('domain' => 'de.systopia.analyser')));
    }
  }

  /**
   * anonymises the contact's contribution information,
   * without deleting statistically relevant data
   */
  protected function anonymiseContributions($contact_id) {
    // TODO: implement
    $this->log(ts("TODO: anonymise contributions", array('domain' => 'de.systopia.analyser')));
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
