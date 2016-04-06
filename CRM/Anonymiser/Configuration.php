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
   * generate an anonymous value to fill the verious fields with.
   * this allows an override based on the field name.
   */
  protected function generateAnonymousValue($field_name) {
    // TODO: do we need anything here?
    return sha1($field_name . microtime(TRUE));
  }

  /**
   * Get a list of table names that will be touched in an 
   * anonymisation process given the current configuration
   */
  public function getAffectedTables() {
    // TODO: implement
    return array('civicrm_contact');
  }

  /**
   * get all entities that should simply be deleted
   */
  public function getEntitiesToDelete() {
    return array(
      'Activity'     => array(),
      'Address'      => array(),
      'Email'        => array(),
      'Phone'        => array(),
      'Attachment'   => array(),
      'File'         => array(),
      'Im'           => array(),
      'Note'         => array(),
      'Relationship' => array(),
      'Website'      => array(),
    );
  }

  /**
   * Get a list of table names that will be touched in an 
   * anonymisation process given the current configuration
   */
  public function getAffectedLogTables() {
    $affected_log_tables = array();
    $affected_tables = $this->getAffectedTables();
    foreach ($affected_tables as $table_name) {
      $affected_log_tables[] = "'$table_name'";
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
