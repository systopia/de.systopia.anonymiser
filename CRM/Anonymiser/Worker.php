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

    // delete ACTIVITIES
    $this->deleteActivities($entity_name, $contact_id, $clearedEntities);

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
      $this->log(ts("%1 attached %2(s) deleted.", array(1 => $counter, 2 => $attachedEntity, 'domain' => 'de.systopia.anonymiser')));
    }

    // FINALLY clean FULL LOGGING tables
    if ($this->config->deleteLogs()) {
      foreach ($clearedEntities as $entity_name => $entity_ids) {
        if (!empty($entity_ids) && $entity_name != 'Log') {
          $table_name     = $this->config->getTableForEntity($entity_name);
          $log_table_name = $this->config->getLogTableForTable($table_name);
          $id_list        = implode(',', $entity_ids);
          $query = "DELETE FROM `$log_table_name` WHERE id IN ($id_list);";
          CRM_Core_DAO::executeQuery($query);
          $this->log(ts("Removed entries for %1 %2(s) from logging table '%3'.", array(1 => count($entity_ids), 2 => $entity_name, 3 => $log_table_name, 'domain' => 'de.systopia.anonymiser')));
        } 
      }

      foreach ($this->config->getAffectedLogTables() as $affected_log_table) {
        $entity_name = $this->config->getEntityForTable($affected_log_table);
        $this->deleteRelatedLogs($entity_name, $contact_id);
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
   * Delete any other log entries related to the contact, even where we did not delete the entity itself
   * (possibly because it had already been deleted, or is no longer related)
   * @param $entity_name string the name of the entity as used by the API
   * @param $contact_id  int    ID of the contact
   */
  protected function deleteRelatedLogs($entity_name, $contact_id) {
    $table_name     = $this->config->getTableForEntity($entity_name);
    $log_table_name = $this->config->getLogTableForTable($table_name);

    $identifiers = $this->config->getIdentifiers($entity_name, $contact_id);
    if ($identifiers['join']) return; // Only entities that refer directly to the contact
    foreach ($identifiers['sql'] as $where_clause) {
      $query = "DELETE FROM `$log_table_name` WHERE $where_clause";
      $result = CRM_Core_DAO::executeQuery($query);
      $row_count = $result->affectedRows();
      if ($row_count) {
        $this->log(ts("Removed %1 additional log entries referencing this contact from logging table '%2'.", array(1 => $row_count, 2 => $log_table_name, 'domain' => 'de.systopia.anonymiser')));
      }
    }
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
    foreach ($identifiers['sql'] as $where_clause) {
      $table_name = $this->config->getTableForEntity($entity_name);
      $join = empty($identifiers['join'])?'':$identifiers['join'];
      $sql = "SELECT `$table_name`.id AS entity_id FROM `$table_name` $join WHERE $where_clause";
      $query = CRM_Core_DAO::executeQuery($sql);
      while ($query->fetch()) {
        $clearedEntities[$entity_name][] = $query->entity_id;
        $this->deleteEntity($entity_name, $query->entity_id);
        $deleted_count++;
      }
    }

    // log this
    $this->log(ts("%1 %2(s) deleted.", array(1 => $deleted_count, 2 => $entity_name, 'domain' => 'de.systopia.anonymiser')));
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
   * DELETE the activities. This is not straightforward, activities
   * can be linked to a multitude of contacts
   * 
   * OUR approach is: if it's linked to up to two contacts, we delete it
   */
  protected function deleteActivities($entity_name, $contact_id, &$clearedEntities) {
    $deleted_activities = 0;
    $deleted_connections = 0;

    // FIRST: find all contact-activity relations
    $identify_connections_sql = "SELECT id FROM civicrm_activity_contact WHERE contact_id = $contact_id";
    $identify_connections = CRM_Core_DAO::executeQuery($identify_connections_sql);
    while ($identify_connections->fetch()) {
      $clearedEntities['ActivityContact'][] = $identify_connections->id;
    }

    // THEN: find and delete the activities
    $identify_activities_sql = "SELECT civicrm_activity.id AS activity_identifier
                                FROM civicrm_activity
                                LEFT JOIN civicrm_activity_contact ON civicrm_activity.id = civicrm_activity_contact.activity_id
                                WHERE contact_id = $contact_id
                                  AND 2 >= (SELECT COUNT(DISTINCT(contact_id)) FROM civicrm_activity_contact WHERE civicrm_activity.id = activity_id );";
    $identify_activities = CRM_Core_DAO::executeQuery($identify_activities_sql);
    while ($identify_activities->fetch()) {
      $activity_id = $identify_activities->activity_identifier;
      $clearedEntities['Activity'][] = $activity_id;
      civicrm_api3('Activity', 'delete', array('id' => $activity_id));
      $deleted_activities += 1;
    }

    // FINALLY: delete any remaining connections (e.g. to mass activities)
    if (!empty($clearedEntities['ActivityContact'])) {
      $deleted_connections = count($clearedEntities['ActivityContact']);
      $entity_list = implode(',', $clearedEntities['ActivityContact']);
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_activity_contact WHERE id IN ($entity_list)");
    }

    $this->log(ts("%1 activities, and %2 associations with activities deleted.", array(1 => $deleted_activities, 2 => $deleted_connections, 'domain' => 'de.systopia.anonymiser')));    
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
        $this->log(ts("Anonymised Membership [%1].", array(1 => $membership['id'], 'domain' => 'de.systopia.anonymiser')));
      } else {
        $this->log(ts("Membership [%1] did not need anonymisation.", array(1 => $membership['id'], 'domain' => 'de.systopia.anonymiser')));
      }
    }

    if ($memberships['count'] == 0) {
      $this->log(ts("0 Membership entities found for anonymisation.", array('domain' => 'de.systopia.anonymiser')));
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
        $this->log(ts("Anonymised Participant [%1].", array(1 => $participant['id'], 'domain' => 'de.systopia.anonymiser')));
      } else {
        $this->log(ts("Participant [%1] did not need anonymisation.", array(1 => $participant['id'], 'domain' => 'de.systopia.anonymiser')));
      }
    }

    if ($participants['count'] == 0) {
      $this->log(ts("0 Participant entities found for anonymisation.", array('domain' => 'de.systopia.anonymiser')));
    }
  }

  /**
   * anonymises the contact's contribution information,
   * without deleting statistically relevant data
   */
  protected function anonymiseContributions($contact_id, &$clearedEntities) {
    $contributions      = civicrm_api3('Contribution', 'get', array('contact_id' => $contact_id, 'option.limit' => 99999));
    $test_contributions = civicrm_api3('Contribution', 'get', array('contact_id' => $contact_id, 'option.limit' => 99999, 'is_test' => 1));
    $all_contributions  = array_merge($contributions['values'], $test_contributions['values']);

    $contribution_counter   = 0;
    $financial_trxn_counter = 0;
    $line_item_counter      = 0;

    // iterate through all contributions
    foreach ($all_contributions as $contribution) {
      $clearedEntities['Contribution'][] = $contribution['id'];
      $fields = $this->config->getOverrideFields('Contribution', $contribution);

      // anonymise the contribution itself
      if (!empty($fields)) {
        $update_query = array('id' => $contribution['id']);
        foreach ($fields as $field_name => $type) {
          $update_query[$field_name] = $this->config->generateAnonymousValue($field_name, $type, $contribution);
        }
        civicrm_api3('Contribution', 'create', $update_query);
        $contribution_counter += 1;          
      }

      // now find and anonymise the LineItems
      $line_items = civicrm_api3('LineItem', 'get', array('contact_id' => $contact_id, 'option.limit' => 99999));
      foreach ($line_items['values'] as $line_item) {
        $clearedEntities['LineItem'][] = $line_item['id'];
        $fields = $this->config->getOverrideFields('LineItem', $line_item);
        if (!empty($fields)) {
          $update_query = array('id' => $line_item['id']);
          foreach ($fields as $field_name => $type) {
            $update_query[$field_name] = $this->config->generateAnonymousValue($field_name, $type, $line_item);
            civicrm_api3('LineItem', 'create', $update_query);
            $line_item_counter += 1;
          }
        }
      }

      // finally: identify all the financial_trxns
      $entity_table = $this->config->getTableForEntity('FinancialTrxn');
      $where_clause = $this->config->getAttachedEntitySelector('FinancialTrxn', $clearedEntities);
      $query = CRM_Core_DAO::executeQuery("SELECT id FROM $entity_table WHERE $where_clause");
      while ($query->fetch()) { 
        // anonymise every one of it
        $financial_trxn_id = $query->id;
        $clearedEntities['FinancialTrxn'][] = $financial_trxn_id;

        $fields = $this->config->getOverrideFields('FinancialTrxn');
        if (!empty($fields)) {
          $update_query = array('id' => $financial_trxn_id);
          foreach ($fields as $field_name => $type) {
            $update_query[$field_name] = $this->config->generateAnonymousValue($field_name, $type);
          }

          civicrm_api3('FinancialTrxn', 'create', $update_query);
          $financial_trxn_counter += 1;
        }
      }
    }
    $this->log(ts("Anonymised %1 contributions, %2 associated line items and %3 associated financial transactions.", array(1 => $contribution_counter, $line_item_counter, $financial_trxn_counter, 'domain' => 'de.systopia.anonymiser')));


    // finally, anonymise recurring contributions
    $recurring_contributions = civicrm_api3('ContributionRecur', 'get', array('contact_id' => $contact_id, 'option.limit' => 99999));
    foreach ($recurring_contributions['values'] as $recurring_contribution) {
      $clearedEntities['ContributionRecur'][] = $recurring_contribution['id'];
      $fields = $this->config->getOverrideFields('ContributionRecur', $recurring_contribution);
      if (!empty($fields)) {
        $update_query = array('id' => $recurring_contribution['id']);
        foreach ($fields as $field_name => $type) {
          $update_query[$field_name] = $this->config->generateAnonymousValue($field_name, $type, $recurring_contribution);
        }
        civicrm_api3('ContributionRecur', 'create', $update_query);
        $this->log(ts("Anonymised RecurringContribution [%1].", array(1 => $recurring_contribution['id'], 'domain' => 'de.systopia.anonymiser')));
      } else {
        $this->log(ts("RecurringContribution [%1] did not need anonymisation.", array(1 => $recurring_contribution['id'], 'domain' => 'de.systopia.anonymiser')));
      }
    }

    if ($recurring_contributions['count'] == 0) {
      $this->log(ts("0 RecurringContribution entities found for anonymisation.", array('domain' => 'de.systopia.anonymiser')));
    }
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
