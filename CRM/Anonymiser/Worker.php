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
    $clearedEntities = array();
    if (empty($contact_id)) throw new Exception(ts("No contact ID given!"));

    // first of all: check if everything's in place
    $this->config->systemCheck();

    // delete entities, that cannot be (sensibly) anonymised
    $entities_to_delete = $this->config->getEntitiesToDelete();
    foreach ($entities_to_delete as $entity_name) {
      $this->deleteRelatedEntities($entity_name, $contact_id, $clearedEntities);
    }

    // ANONYMISE memberships
    if (!$this->config->deleteMemberships()) {
      $this->anonymiseMemberships($contact_id, $clearedEntities);
    }

    // ANONYMISE participants
    if (!$this->config->deleteParticipations()) {
      $this->anonymiseParticipants($contact_id, $clearedEntities);
    }

    // ANONYMISE contributions
    if (!$this->config->deleteContributions()) {
      $this->anonymiseContributions($contact_id, $clearedEntities);
    }

    // THEN: clean out the basic data
    $this->anoymiseContactBase($contact_id, $clearedEntities);


    // NOW: FIRST FIND 'free' attached entities, i.e. entities that can be connected to 
    //  any of the processed entities
    $attachedEntities = $this->config->getAttachedEntities();
    foreach ($attachedEntities as $attachedEntity) {
      $counter = 0;
      // get a selector to find all entities attached to any of the deleted/anonymised ones
      $entity_table = $this->config->getTableForEntity($attachedEntity);
      $where_clause = $this->config->getAttachedEntitySelector($attachedEntity, $clearedEntities);
      $query = CRM_Core_DAO::executeQuery("SELECT id FROM $entity_table WHERE $where_clause");
      while ($query->fetch()) { 
        // delete right away, if not in the list already
        if (empty($clearedEntities[$attachedEntity]) || !in_array($query->id, $clearedEntities[$attachedEntity])) {
          $this->deleteEntity($attachedEntity, $query->id);
          $clearedEntities[$attachedEntity][] = $query->id;
          $counter += 1;          
        }
      }
      $this->log(ts("%1 attached %2(s) deleted.", array(1 => $counter, 2 => $attachedEntity, 'domain' => 'de.systopia.analyser')));
    }

    // FINALLY clean FULL LOGGING tables
    if ($this->config->deleteLogs()) {
      foreach ($clearedEntities as $entity_name => $entity_ids) {
        if (!empty($entity_ids) && $entity_name != 'Log') {
          $table_name     = $this->config->getTableForEntity($entity_name);
          $log_table_name = $this->config->getLogTableForTable($table_name);
          $id_list        = implode(',', $entity_ids);
          $query = "DELETE FROM `$log_table_name` WHERE id IN ($id_list);";
          error_log($query);
          CRM_Core_DAO::executeQuery($query);
          $this->log(ts("Removed entries for %1 %2(s) from logging table '%3'.", array(1 => count($entity_ids), 2 => $entity_name, 3 => $log_table_name, 'domain' => 'de.systopia.analyser')));
        } 
      }
    }
  }




  /**
   * Anonymise the contact base
   */
  protected function anoymiseContactBase($contact_id, &$clearedEntities) {
    // first: load the contact
    $clearedEntities['Contact'][] = $contact_id;
    $contact = civicrm_api3('Contact', 'getsingle', array('id' => $contact_id));

    // then: get all fields to overwrite
    $fields = $this->config->getOverrideFields('Contact', $contact);
    $erase_query = array('id' => $contact_id);
    foreach ($fields as $field_name => $anon_type) {
      $erase_query[$field_name] = $this->config->generateAnonymousValue($field_name, $anon_type, $contact);
    }

    civicrm_api3('Contact', 'create', $erase_query);

    // TODO: anything else?
  }

  /**
   * Delete an entity that is related to the contact
   * @param $entity_name string the name of the entity as used by the API
   * @param $entity_spec array  parameters used for identification
   */
  protected function deleteRelatedEntities($entity_name, $contact_id, &$clearedEntities) {
    $deleted_count = 0;

    // first: find all entities
    $identifiers = $this->config->getIdentifiers($entity_name, $contact_id);
    foreach ($identifiers['api'] as $query) {
      $query['limit.option'] = 999999;
      $query['return'] = 'id';
      $entities_found = civicrm_api3($entity_name, 'get', $query);

      // delete them all
      foreach ($entities_found['values'] as $key => $entity) {
        $clearedEntities[$entity_name][] = $entity['id'];
        $this->deleteEntity($entity_name, $entity['id']);
        $deleted_count++;
      }
    }

    // log this
    $this->log(ts("%1 %2(s) deleted.", array(1 => $deleted_count, 2 => $entity_name, 'domain' => 'de.systopia.analyser')));
  }

  /**
   * delete an individual entity
   */
  protected function deleteEntity($entity_name, $entity_id)  {
    if ($entity_name=='Log') {
      // exception for Log entries (no API)
      $table_name = $this->config->getTableForEntity($entity_name);
      CRM_Core_DAO::executeQuery("DELETE FROM `$table_name` WHERE id = $entity_id");
    } else {
      civicrm_api3($entity_name, 'delete', array('id' => $entity_id));
    }
  }


  /**
   * anonymises the contact's membership information,
   * without deleting statistically relevant data
   */
  protected function anonymiseMemberships($contact_id, &$clearedEntities) {
    $memberships = civicrm_api3('Membership', 'get', array('contact_id' => $contact_id, 'option.limit' => 99999));

    // iterate through all memberships
    foreach ($memberships['values'] as $membership) {
      $clearedEntities['Membership'][] = $membership['id'];
      $fields = $this->config->getOverrideFields('Membership', $membership);
      if (!empty($fields)) {
        $update_query = array('id' => $membership['id']);
        foreach ($fields as $field_name => $type) {
          $update_query[$field_name] = $this->config->generateAnonymousValue($field_name, $type, $membership);
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
  protected function anonymiseParticipants($contact_id, &$clearedEntities) {
    $participants = civicrm_api3('Participant', 'get', array('contact_id' => $contact_id, 'option.limit' => 99999));
    // iterate through all participants
    foreach ($participants['values'] as $participant) {
      $clearedEntities['Participant'][] = $participant['id'];
      $fields = $this->config->getOverrideFields('Participant', $participant);
      if (!empty($fields)) {
        $update_query = array('id' => $participant['id']);
        foreach ($fields as $field_name => $type) {
          $update_query[$field_name] = $this->config->generateAnonymousValue($field_name, $type, $participant);
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
  protected function anonymiseContributions($contact_id, &$clearedEntities) {
    // TODO: implement
    $this->log(ts("TODO: anonymise contributions", array('domain' => 'de.systopia.analyser')));
    // $clearedEntities['Contact'] = array($contact_id);
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
