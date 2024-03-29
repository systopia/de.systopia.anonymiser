<?php
/*-------------------------------------------------------+
| SYSTOPIA Anonymiser                                    |
| Copyright (C) 2021 SYSTOPIA                            |
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

use CRM_Anonymiser_ExtensionUtil as E;

/**
 * Contains a bunch of contact IDs to be anonymised
 */
class CRM_Anonymiser_AnonymiserJob {

  /** @var string $title Will be set as title by the runner. */
  public $title;

  /** @var $contact_ids array */
  protected $contact_ids;

  /**
   * Anonymiser Job
   *
   * @param array $contact_ids
   *   list of contact IDs to be anonymised
   *
   * @param string $next_title
   *   title/caption of the _next_ item in the queue,
   *     as it's only been displayed after it's been executed
   *
   * @param string $log_file
   *   File path to the log file
   */
  public function __construct($contact_ids, $next_title, $log_file) {
    $this->title = $next_title;
    $this->contact_ids = $contact_ids;
    $this->log_file = $log_file;
  }

  /**
   * Run the anonymise process for all of them
   *
   * @return true
   */
  public function run(): bool
  {
    // create a worker instance
    $anonymiser = new CRM_Anonymiser_Worker();

    // anonymise all contacts
    foreach ($this->contact_ids as $contact_id) {
      $anonymiser->log("\n");
      $anonymiser->log(E::ts("Anonymisation of contact [%1]", [1 => $contact_id]));
      $anonymiser->log('--------------------------------------------------------');
      $anonymiser->anonymiseContact($contact_id);
    }

    // append log
    file_put_contents(
        $this->log_file,
        implode("\n", $anonymiser->getLog()),
        FILE_APPEND
    );

    return true;
  }

}
