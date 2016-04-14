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

    

    parent::run();
  }
}
