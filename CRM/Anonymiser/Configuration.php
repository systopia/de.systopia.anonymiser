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

      if ($this->shouldDelete('contact_dates')) {
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
   * generate an anonymous value to fill the verious fields with.
   * this allows an override based on the field name.
   */
  public function generateAnonymousValue($field_name, $type = 'string') {
    switch ($type) {
      case 'string':
        // generate random string
        return sha1($field_name . microtime(TRUE));

      case 'null':
        return 'null'; 
      
      default:
        error_log("UNDEFINED: $type");
        return 'null';
    }
  }


  /**
   * get all entities that should simply be deleted
   */
  public function getEntitiesToDelete() {
    // TODO: ask configuration?
    $entities = array(
      'Activity'     => array(),
      'Address'      => array(),
      'Email'        => array(),
      'Phone'        => array(),
      'File'         => array(),
      'Im'           => array(),
      'Note'         => array('has_entity_relation' => 1, ),
      'Relationship' => array(),
      'Website'      => array(),
    );

    // add default fields
    foreach ($entities as $entity_name => &$entity_spec) {
      if (empty($entity_spec['table_name'])) {
        $entity_spec['table_name'] = 'civicrm_' . strtolower($entity_name);
      }
    }

    return $entities;
  }

  /**
   * Get a list of table names that will be touched in an 
   * anonymisation process given the current configuration
   */
  public function getAffectedTables() {
    $affected_tables = array('civicrm_contact');

    // add all entity tables
    $entities = $this->getEntitiesToDelete();
    foreach ($entities as $entity_name => $entity_spec) {
      $affected_tables[] = $entity_spec['table_name'];
    }

    return $affected_tables;
  }


  /**
   * Get a list of table names that will be touched in an 
   * anonymisation process given the current configuration
   */
  public function getAffectedLogTables() {
    $affected_log_tables = array();
    $affected_tables = $this->getAffectedTables();
    foreach ($affected_tables as $table_name) {
      $affected_log_tables[] = "'log_$table_name'";
    }
    return $affected_log_tables;
  }

  /**
   * Should the logs also be anonymised?
   * This is also FALSE if the user requested it, but there is
   * no log_ tables present.
   */
  public function anonymiseLogs() {
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
    if ($this->anonymiseLogs()) {
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
  }
}
