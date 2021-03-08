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

require_once 'CRM/Core/Page.php';

class CRM_Anonymiser_Page_Anonymise extends CRM_Core_Page {
  public function run() {
    CRM_Utils_System::setTitle(ts('Anonymise Contact', array('domain' => 'de.systopia.anonymiser')));

    if (empty($_REQUEST['cid'])) {
      $contact_id = 0;
    } else {
      $contact_id = (int) $_REQUEST['cid'];
    }

    if ($contact_id) {
      $contact = civicrm_api3('Contact', 'getsingle', array('id' => $contact_id));
      $this->assign('contact', $contact);
      parent::run();
    } else {
      CRM_Core_Session::setStatus(ts('Contact ID is invalid!', array('domain' => 'de.systopia.anonymiser')), ts('Error', array('domain' => 'de.systopia.anonymiser')), 'error');
      CRM_Utils_System::civiExit();
    }
  }
}
