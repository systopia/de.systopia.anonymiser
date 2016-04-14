<?php
/*-------------------------------------------------------+
| SYSTOPIA Contact Anonymiser Extension                  |
| Copyright (C) 2016 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

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
