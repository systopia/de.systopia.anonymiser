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
 * This class wraps all configuraition options
 * for the anonymisation process
 */
class CRM_Anonymiser_Configuration {

  /** the name of the database used */
  protected $database_name = NULL;

  public function __construct() {
    $dao = new CRM_Core_DAO();
    $this->database_name = $dao->database();
  }

  /**
   * get a list of fields to override for the given entity
   *
   * @param $entity_name  array  the entity name as used by the API
   * @param $entity       array  entity data
   * 
   * @return array field_name => field_type map
   */
  public function getOverrideFields($entity_name, $entity = array()) {
    if ($entity_name == 'Contact') {
      $fields = array(
        "legal_identifier"       => 'null',
        "external_identifier"    => 'null',
        "nick_name"              => 'null',
        "legal_name"             => 'null',
        "image_URL"              => 'null',
        "preferred_language"     => 'null',
        "source"                 => 'null',
        "api_key"                => 'null',
        "middle_name"            => 'null',
        "formal_title"           => 'null',
        "job_title"              => 'null',
        "primary_contact_id"     => 'null',
        "sic_code"               => 'null',
        "user_unique_id"         => 'null',
        "gender_id"              => 'null',
        "employer_id"            => 'null',
        "first_name"             => 'string',
        "last_name"              => 'string',
        "household_name"         => 'string',
        "organisation_name"      => 'string',
        "postal_greeting_custom" => 'null',
        "email_greeting_custom"  => 'null',
        "addressee_custom"       => 'null',
        "postal_greeting_id"     => 'null',
        "email_greeting_id"      => 'null',
        "addressee_id"           => 'null',
      );

      if ($this->shouldDeleteAttribute('contact_dates')) {
        $fields['birth_date']    = 'null';
        $fields['deceased_date'] = 'null';
        $fields['created_date']  = 'null';
      }
    } else {
      // TODO:
      error_log("NOT IMPLEMENTED");
      $fields = array();
    }

    return $fields;
  }

  /**
   * looks into the settings, if certain key should be deleted/reset
   */
  public function shouldDeleteAttribute($key) {
    // TODO: look into config
    return TRUE;
  }

  /**
   * generate an anonymous value to fill the verious fields with.
   * this allows an override based on the field name.
   */
  public function generateAnonymousValue($field_name, $type = 'string') {
    switch ($type) {
      case 'string':
        // generate random string
        return sha1($field_name . microtime(TRUE));

      case 'null':
        return ''; 
      
      default:
        error_log("UNDEFINED: $type");
        return 'null';
    }
  }

  /**
   * get the table name for an entity
   */
  public function getTableForEntity($entity_name) {
    // TODO: exceptions?

    // first: split camel case
    $table_name = preg_replace("/([a-z])([A-Z])/", "\\1_\\2", $entity_name);

    // then prepend civicrm_ and return lower case
    return $entity_spec['table_name'] = 'civicrm_' . strtolower($table_name);
  }


  /**
   * get the entity for a table name
   */
  public function getEntityForTable($table_name) {
    // TODO: exceptions?

    // first: strip the 'log_' if present
    if (substr($table_name, 0, 4) == 'log_') {
      $table_name = substr($table_name, 4);
    }

    // then: strip the 'civicrm_' if present
    if (substr($table_name, 0, 8) == 'civicrm_') {
      $table_name = substr($table_name, 8);
    }

    // then: replace all remaining '_' by capitalising the following character
    $entity_name = '';
    $parts = preg_split("/_/", $table_name);
    foreach ($parts as $name_part) {
      $entity_name .= strtoupper(substr($name_part, 0, 1)) . substr($name_part, 1);
    }

    return $entity_name;
  }


  /**
   * get all entities that should simply be deleted
   */
  public function getEntitiesToDelete() {
    // basic setup
    $entities = array(
      'Activity'
      'Address'
      'Email'
      'Phone'
      'File'
      'Im'
      'Note'
      'Relationship'
      'Website'
    );

    if ($this->config->deleteGroups()) {
      $entities[] = 'GroupContact';
    }

    if ($this->config->deleteTags()) {
      $entities[] = 'EntityTag';
    }

    if ($this->config->deleteMemberships()) {
      $entities[] = 'Membership';
    }

    if ($this->config->deleteParticipations()) {
      $entities[] = 'Participant';
    }

    if ($this->config->deleteContributions()) {
      $entities[] = 'Contribution';
      $entities[] = 'ContributionRecur';
    }

    return $entities;
  }

  /**
   * Generate the API and SQL lookup data
   * to indentify the affected records
   */
  public function getIdentifiers($entity_name, $contact_id) {
    // TODO: exception for Notes
    // TODO: exception for Activities

    // This is the standard case
    if ($this->isEntityRelationScheme($entity_name)) {
      return array('api' => array( array('entity_table' => 'civicrm_contact',
                                         'entity_id'    => $contact_id)),
                   'sql' => array( "`entity_table`='civicrm_contact' AND `entity_id` = $contact_id"));
    } else {
      return array('api' => array( array('contact_id' => $contact_id)),
                   'sql' => array( "`contact_id` = $contact_id"));
    }
  }

  /**
   * Get a list of table names that will be touched in an 
   * anonymisation process given the current configuration
   */
  public function getAffectedTables() {
    $affected_tables = array('civicrm_contact');

    // get all entities that were to be deleted
    $entities = $this->getEntitiesToDelete();

    // add the ones that were just anonymised
    if (!in_array('Contact', $entities))           $entities[] = 'Contact';
    if (!in_array('GroupContact', $entities))      $entities[] = 'GroupContact';
    if (!in_array('EntityTag', $entities))         $entities[] = 'EntityTag';
    if (!in_array('Membership', $entities))        $entities[] = 'Membership';
    if (!in_array('Participant', $entities))       $entities[] = 'Participant';
    if (!in_array('Contribution', $entities))      $entities[] = 'Contribution';
    if (!in_array('ContributionRecur', $entities)) $entities[] = 'ContributionRecur';

    // look up the table names
    foreach ($entities as $entity_name) {
      $affected_tables[] = $this->getTableForEntity($entity_name);
    }

    return $affected_tables;
  }

  /**
   * get the corresponding log table
   */
  public function getLogTableForTable($table_name) {
    return "'log_$table_name'";
  }

  /**
   * Get a list of table names that will be touched in an 
   * anonymisation process given the current configuration
   */
  public function getAffectedLogTables() {
    $affected_log_tables = array();
    $affected_tables = $this->getAffectedTables();
    foreach ($affected_tables as $table_name) {
      $affected_log_tables[] = $this->getLogTableForTable($table_name);
    }
    return $affected_log_tables;
  }

  /**
   * Should the logs also be anonymised?
   * This is also FALSE if the user requested it, but there is
   * no log_ tables present.
   */
  public function deleteLogs() {
    // TODO: read config
    $anonymise_logs = TRUE;
    if (!$anonymise_logs) return FALSE;

    // get the tables  
    $affected_log_tables = $this->getAffectedLogTables();
    $affected_log_table_list = implode(',', $affected_log_tables);

    $log_tables_present = "SELECT COUNT(table_name) FROM information_schema.tables WHERE table_schema = '{$this->database_name}' AND table_name IN ($affected_log_table_list);";
    return CRM_Core_DAO::singleValueQuery($log_tables_present);
  }






  /**
   * Will check if the system is ready for
   * an anonymisation process under the current 
   * configuration
   *
   * @throws Exception if system not ready.
   */
  public function systemCheck() {
    if ($this->deleteLogs()) {
      $affected_log_tables = $this->getAffectedLogTables();
      $affected_log_table_list = implode(',', $affected_log_tables);

      // get the tables
      $archive_check = "SELECT COUNT(table_name) FROM information_schema.tables WHERE table_schema = '{$this->database_name}' AND table_name IN ($affected_log_table_list) AND engine = 'ARCHIVE';";
      error_log($archive_check);
      $archives_present = CRM_Core_DAO::singleValueQuery($archive_check);
      if ($archives_present) {
        throw new Exception("TODO: ARCHIVE TABLES PRESENT!");
      }
    }

    // TODO: WARNING WHEN CUSTOM FIELDS FOR ANONYMISED (NOT DELETED) ENTITIES
  }
}
