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

class CRM_Anonymiser_Page_Anonymise extends CRM_Core_Page {

  /**
   * @return void
   */
  public function run() {
    CRM_Utils_System::setTitle(ts('Anonymise Contact', ['domain' => 'de.systopia.anonymiser']));

    $cid = $_REQUEST['cid'] ?? NULL;
    if ((!is_string($cid) && !is_int($cid)) || $cid === '' || $cid === '0') {
      $contact_id = 0;
    }
    else {
      $contact_id = (int) $cid;
    }

    if ($contact_id !== 0) {
      $contact = civicrm_api3('Contact', 'getsingle', ['id' => $contact_id]);
      $this->assign('contact', $contact);
      parent::run();
    }
    else {
      CRM_Core_Session::setStatus(
        ts('Contact ID is invalid!', ['domain' => 'de.systopia.anonymiser']),
        ts('Error', ['domain' => 'de.systopia.anonymiser']),
        'error'
      );
      CRM_Utils_System::civiExit();
    }
  }

}
