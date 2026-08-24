<?php
/*-------------------------------------------------------+
| SYSTOPIA Anonymiser                                    |
| Copyright (C) 2016-2021 SYSTOPIA                       |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Anonymiser_ExtensionUtil as E;

/**
 * This class wraps all configuraition options
 * for the anonymisation process
 */
class CRM_Anonymiser_Configuration {

  /**
   * @var string|null the name of the database used
   */
  protected $database_name = NULL;

  public function __construct() {
    $dao = new CRM_Core_DAO();
    $this->database_name = $dao->database();
  }

  /**
   * if this returns true, the configuration
   * wants all tags for the contact to be deleted
   * otherwise they will remain unchanged
   *
   * @return bool
   */
  public function deleteTags() {
    return (bool) \Civi::settings()->get('anonymiser_tags');
  }

  /**
   * if this returns true, the configuration
   * wants all groups associations for the contact
   * to be deleted, otherwise they will remain unchanged
   *
   * @return bool
   */
  public function deleteGroups() {
    return (bool) \Civi::settings()->get('anonymiser_groups');
  }

  /**
   * if this returns true, the configuration
   * wants memberships to be deleted, otherwise
   * they should be anonymised
   *
   * @return bool
   */
  public function deleteMemberships() {
    return (bool) \Civi::settings()->get('anonymiser_memberships');
  }

  /**
   * if this returns true, the configuration
   * wants event participations to be deleted,
   * otherwise they should be anonymised
   *
   * @return bool
   */
  public function deleteParticipations() {
    return (bool) \Civi::settings()->get('anonymiser_participants');
  }

  /**
   * if this returns true, the configuration
   * wants contributions to be deleted, otherwise
   * they should be anonymised
   *
   * @return bool
   */
  public function deleteContributions() {
    return (bool) \Civi::settings()->get('anonymiser_contributions');
  }

  /**
   * looks into the settings, if certain key should be deleted/reset
   *
   * @param string $key
   *
   * @return bool
   */
  public function shouldDeleteAttribute($key) {
    if (\Civi::settings()->hasExplicit('anonymiser_' . $key)) {
      return (bool) \Civi::settings()->get('anonymiser_' . $key);
    }
    return TRUE;
  }

  /**
   * get a list of fields to override for the given entity
   *
   * @param string $entity_name the entity name as used by the API
   * @param array<string, mixed> $entity entity data
   *
   * @return array<string, string> field_name => field_type map
   */
  public function getOverrideFields($entity_name, $entity = []) {
    if ($entity_name === 'Contact') {
      $fields = [
        'legal_identifier'       => 'null',
        'external_identifier'    => 'null',
        'nick_name'              => 'null',
        'legal_name'             => 'null',
        'image_URL'              => 'null',
        'preferred_language'     => 'null',
        'source'                 => 'null',
        'api_key'                => 'null',
        'formal_title'           => 'null',
        'job_title'              => 'null',
        'primary_contact_id'     => 'null',
        'sic_code'               => 'null',
        'user_unique_id'         => 'null',
        'gender_id'              => 'null',
        'employer_id'            => 'null',
        'first_name'             => 'null',
        'middle_name'            => 'null',
        'last_name'              => 'anon_name',
        'household_name'         => 'anon_name',
        'organisation_name'      => 'anon_name',
        'postal_greeting_custom' => 'null',
        'email_greeting_custom'  => 'null',
        'addressee_custom'       => 'null',
        'postal_greeting_id'     => 'null',
        'postal_greeting_display' => 'null',
        'email_greeting_id'      => 'null',
        'email_greeting_display' => 'null',
        'addressee_id'           => 'null',
        'addressee_display'      => 'null',
        'birth_date'             => 'null',
        'deceased_date'          => 'null',
        'created_date'           => 'null',
        'is_deleted'             => 'true',
      ];

      if (!$this->shouldDeleteAttribute('contact_dates')) {
        $fields['birth_date']    = 'month_floor';
        $fields['deceased_date'] = 'month_floor';
        $fields['created_date']  = 'year_floor';
      }

    }
    elseif ($entity_name === 'Membership') {
      $fields = [
        'join_date'              => 'year_floor',
        'start_date'             => 'year_floor',
        'end_date'               => 'year_ceil',
        'source'                 => 'null',
      ];

    }
    elseif ($entity_name === 'Participant') {
      $fields = [
        'register_date'          => 'month_floor',
        'source'                 => 'null',
      ];

    }
    elseif ($entity_name === 'Contribution') {
      $fields = [
        'source'                 => 'null',
        'trxn_id'                => 'null',
        'invoice_id'             => 'null',
        'check_number'           => 'null',
        'credit_note_id'         => 'null',
      ];

    }
    elseif ($entity_name === 'ContributionRecur') {
      $fields = [
        'trxn_id'                => 'null',
        'invoice_id'             => 'null',
      ];

    }
    elseif ($entity_name === 'FinancialTrxn') {
      $fields = [
        'trxn_id'                => 'null',
        'check_number'           => 'null',
      ];

    }
    else {
      // TODO: Check if we forgot something...
      $fields = [];
    }

    return $fields;
  }

  /**
   * generate an anonymous value to fill the verious fields with.
   * this allows an override based on the field name.
   *
   * @param string $field_name
   * @param string $type
   * @param array<string, mixed> $entity
   *
   * @return string
   */
  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
  public function generateAnonymousValue($field_name, $type = 'string', $entity = []) {
    switch ($type) {
      case 'anon_name':
        return "{$entity['contact_type']}-{$entity['id']}";

      case 'sha1':
        // generate random string
        return sha1($field_name . microtime(TRUE));

      case 'null':
        return '';

      case 'true':
        return '1';

      case 'false':
        return '0';

      case 'year_floor':
      case 'year_ceil':
      case 'month_floor':
        if (isset($entity[$field_name]) && $entity[$field_name] !== '') {
          $date = strtotime($entity[$field_name]);
          if ($type === 'year_floor') {
            return date('Y0101000000', $date);
          }
          elseif ($type === 'year_ceil') {
            return date('Y1231000000', $date);
          }
          else {
            return date('Ym01000000', $date);
          }
        }
        return '';

      default:
        error_log("UNDEFINED: $type");
        return 'null';
    }
  }

  /**
   * get the table name for an entity
   *
   * @param string $entity_name
   *
   * @return string
   */
  public function getTableForEntity($entity_name) {
    // TODO: exceptions?

    // first: split camel case
    $table_name = preg_replace('/([a-z])([A-Z])/', "\\1_\\2", $entity_name);

    // then prepend civicrm_ and return lower case
    return 'civicrm_' . strtolower($table_name);
  }

  /**
   * get the entity for a table name
   *
   * @param string $table_name
   *
   * @return string
   */
  public function getEntityForTable($table_name) {
    // TODO: exceptions?

    // first: strip the 'log_' if present
    if (substr($table_name, 0, 4) === 'log_') {
      $table_name = substr($table_name, 4);
    }

    // then: strip the 'civicrm_' if present
    if (substr($table_name, 0, 8) === 'civicrm_') {
      $table_name = substr($table_name, 8);
    }

    // then: replace all remaining '_' by capitalising the following character
    $entity_name = '';
    $parts = preg_split('/_/', $table_name);
    foreach ($parts as $name_part) {
      $entity_name .= strtoupper(substr($name_part, 0, 1)) . substr($name_part, 1);
    }

    return $entity_name;
  }

  /**
   * get all entities that should simply be deleted
   *
   * @return array<int, string>
   */
  public function getEntitiesToDelete() {
    // basic setup
    $entities = [
      'Address',
      'Email',
      'Phone',
      'File',
      'Im',
      'Note',
      'Relationship',
      'Website',
    ];

    if ($this->deleteGroups()) {
      $entities[] = 'GroupContact';
    }

    if ($this->deleteTags()) {
      $entities[] = 'EntityTag';
    }

    if ($this->deleteMemberships()) {
      $entities[] = 'Membership';
    }

    if ($this->deleteParticipations()) {
      $entities[] = 'Participant';
    }

    if ($this->deleteContributions()) {
      $entities[] = 'Contribution';
      $entities[] = 'ContributionRecur';
    }

    return $entities;
  }

  /**
   * Attached entities can be freely connected
   * to any of our contact's entities via
   * entity_table/entity_id relation or EntityEntity table
   *
   * @return array<int, string>
   */
  public function getAttachedEntities() {
    $entities = [
      'Note',
      'Log',
      'File',
    ];

    if ($this->deleteTags()) {
      $entities[] = 'EntityTag';
    }

    return $entities;
  }

  /**
   * Generate the API and SQL lookup data
   * to indentify the affected records
   *
   * @param string $entity_name
   * @param int $contact_id
   *
   * @return array<string, mixed>
   */
  public function getIdentifiers($entity_name, $contact_id) {
    // notes have both, entity_table and contact_id (creator)
    // NOTES have both:
    if ($entity_name === 'Note') {
      return [
        'api' => [[
          'entity_table' => 'civicrm_contact',
          'entity_id'    => $contact_id,
        ],
                                   [['contact_id' => $contact_id]],
        ],
        'sql' => ["(`entity_table`='civicrm_contact' AND `entity_id` = $contact_id) OR (`contact_id` = $contact_id)"],
      ];
    }

    // Contact has the ID right there
    if ($entity_name === 'Contact') {
      return [
        'api' => [[['id' => $contact_id]]],
        'sql' => ["(`id` = $contact_id)"],
      ];
    }

    // Activities are exceptional
    if ($entity_name === 'Activity') {
      return [
        'api' => [[['source_contact_id' => $contact_id],
                                          ['target_contact_id' => $contact_id],
        ],
        ],
        'join' => 'LEFT JOIN civicrm_activity_contact ON activity_id=civicrm_activity.id',
        'sql' => ["(`contact_id` = $contact_id)"],
      ];
    }

    // Files are exceptional
    if ($entity_name === 'File') {
      // TODO: API??
      return [
        'api' => [],
        'join' => 'LEFT JOIN civicrm_entity_file ON file_id=civicrm_file.id',
        'sql' => ["(`entity_table`='civicrm_contact' AND `entity_id` = $contact_id)"],
      ];
    }

    // Relationships are exceptional
    if ($entity_name === 'Relationship') {
      return [
        'api' => [[['contact_id_a' => $contact_id],
                                          ['contact_id_b' => $contact_id],
        ],
        ],
        'sql' => ["(`contact_id_a` = $contact_id OR `contact_id_b` = $contact_id)"],
      ];
    }

    if (in_array($entity_name, ['EntityTag', 'File', 'Log'], TRUE)) {
      // This is an entity_relation scheme
      return [
        'api' => [[
          'entity_table' => 'civicrm_contact',
          'entity_id'    => $contact_id,
        ],
        ],
        'sql' => ["`entity_table`='civicrm_contact' AND `entity_id` = $contact_id"],
      ];
    }

    // This is the standard case
    return [
      'api' => [['contact_id' => $contact_id]],
      'sql' => ["`contact_id` = $contact_id"],
    ];
  }

  /**
   * generate a SQL WHERE claue to identify all instances
   * attached to the list of cleared entities
   *
   * @param string $entity_name
   * @param array<string, array<mixed, mixed>> $clearedEntities
   *
   * @return string
   */
  public function getAttachedEntitySelector($entity_name, $clearedEntities) {
    // otherwise, just create selectors for all cleared entities
    $clauses = [];
    foreach ($clearedEntities as $clearedEntity => $entity_ids) {
      if ($entity_ids !== []) {
        $table_name = $this->getTableForEntity($clearedEntity);
        $id_list    = implode(',', $entity_ids);
        $clauses[] = "(`entity_table` = '$table_name' AND `entity_id` IN ($id_list))";
      }
    }

    if ($entity_name === 'File' || $entity_name === 'FinancialTrxn') {
      // File and FinancialTrxn is an exception:
      $entity_file_table = $this->getTableForEntity('Entity' . $entity_name);
      $selector = implode(' OR ', $clauses);
      $id_field = ($entity_name === 'File') ? 'file_id' : 'financial_trxn_id';
      return "id IN (SELECT $id_field AS id FROM `$entity_file_table` WHERE $selector)";
    }
    else {
      // standard
      return implode(' OR ', $clauses);
    }
  }

  /**
   * Get a list of table names that will be touched in an
   * anonymisation process given the current configuration
   *
   * @return array<int, string>
   */
  public function getAffectedTables() {
    $affected_tables = ['civicrm_contact'];

    // get all entities that were to be deleted
    $entities = $this->getEntitiesToDelete();

    // add the ones that were just anonymised
    if (!in_array('Contact', $entities, TRUE)) {
      $entities[] = 'Contact';
    }
    if (!in_array('GroupContact', $entities, TRUE)) {
      $entities[] = 'GroupContact';
    }
    if (!in_array('EntityTag', $entities, TRUE)) {
      $entities[] = 'EntityTag';
    }
    if (!in_array('Membership', $entities, TRUE)) {
      $entities[] = 'Membership';
    }
    if (!in_array('Participant', $entities, TRUE)) {
      $entities[] = 'Participant';
    }
    if (!in_array('Contribution', $entities, TRUE)) {
      $entities[] = 'Contribution';
    }
    if (!in_array('ContributionRecur', $entities, TRUE)) {
      $entities[] = 'ContributionRecur';
    }

    // look up the table names
    foreach ($entities as $entity_name) {
      $affected_tables[] = $this->getTableForEntity($entity_name);
    }

    return $affected_tables;
  }

  /**
   * get the corresponding log table
   *
   * @param string $table_name
   *
   * @return string
   */
  public function getLogTableForTable($table_name) {
    return "log_$table_name";
  }

  /**
   * Get a list of table names in quotes that will be touched in an
   * anonymisation process given the current configuration
   *
   * @param string $quotation
   *
   * @return array<int, string>
   */
  public function getAffectedLogTables($quotation = '') {
    $affected_log_tables = [];
    $affected_tables = $this->getAffectedTables();
    foreach ($affected_tables as $table_name) {
      $affected_log_tables[] = $quotation . $this->getLogTableForTable($table_name) . $quotation;
    }
    return $affected_log_tables;
  }

  /**
   * Should the logs also be anonymised?
   * This is FALSE if there are no log_ tables present.
   *
   * @return string|null
   */
  public function deleteLogs() {
    // get the tables
    $affected_log_tables = $this->getAffectedLogTables("'");
    $affected_log_table_list = implode(',', $affected_log_tables);

    $log_tables_present = 'SELECT COUNT(table_name) FROM information_schema.tables '
      . "WHERE table_schema = '{$this->database_name}' AND table_name IN ($affected_log_table_list);";
    return CRM_Core_DAO::singleValueQuery($log_tables_present);
  }

  /**
   * Will check if the system is ready for
   * an anonymisation process under the current
   * configuration
   *
   * @throws RuntimeException if system not ready.
   *
   * @return void
   */
  public function systemCheck() {
    if ((int) $this->deleteLogs() > 0) {
      $affected_log_tables = $this->getAffectedLogTables("'");
      $affected_log_table_list = implode(',', $affected_log_tables);

      // get the tables
      $archive_check = 'SELECT COUNT(table_name) FROM information_schema.tables '
        . "WHERE table_schema = '{$this->database_name}' AND table_name IN ($affected_log_table_list) "
        . "AND engine = 'ARCHIVE';";
      $archives_present = CRM_Core_DAO::singleValueQuery($archive_check);
      if ((int) $archives_present > 0) {
        throw new RuntimeException('TODO: ARCHIVE TABLES PRESENT!');
      }
    }

    // TODO: WARNING WHEN CUSTOM FIELDS FOR ANONYMISED (NOT DELETED) ENTITIES
  }

  /**
   * Return the attached custom tables for this entity
   *
   * @param string $entity
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public function getCustomTablesForEntity($entity) {
    if (!isset(\Civi::$statics[E::LONG_NAME]['custom_tables'][$entity])
      && !is_array(\Civi::$statics[E::LONG_NAME]['custom_tables'][$entity])
    ) {
      \Civi::$statics[E::LONG_NAME]['custom_tables'][$entity] = [];
      $extends = [$entity];
      switch ($entity) {
        case 'Participant':
          $extends[] = 'ParticipantRole';
          $extends[] = 'ParticipantEventName';
          $extends[] = 'ParticipantEventType';
          break;

        case 'Contact':
          $extends[] = 'Individual';
          $extends[] = 'Household';
          $extends[] = 'Organization';
          break;
      }
      $result = civicrm_api3('CustomGroup', 'get', [
        'sequential' => 1,
        'return' => ['table_name'],
        'extends' => ['IN' => $extends],
        'options' => ['limit' => 0],
      ]);
      foreach ($result['values'] as $custom_group) {
        \Civi::$statics[E::LONG_NAME]['custom_tables'][$entity][] = $custom_group['table_name'];
      }
    }
    return \Civi::$statics[E::LONG_NAME]['custom_tables'][$entity];
  }

}
