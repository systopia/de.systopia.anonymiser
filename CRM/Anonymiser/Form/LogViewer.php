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
 * Enables you to view an anonymiser log
 */
class CRM_Anonymiser_Form_LogViewer extends CRM_Core_Form {

  /** @var string distinct file prefix to prevent abuse of the log file viewer */
  const LOG_FILE_PREFIX = 'anonymiser_log_77ce8d46c26598e8073e2de039b7dd5cb637cf30';

  /**
   * Verify that this is our log file
   *
   * @throws Exception
   *   if there's something wrong with the log file
   */
  protected function verifyLogFile() {
    if (!is_readable($this->log_file)) {
      throw new Exception(E::ts("Log file doesn't exist or is not accessible"));
    }

    if (strstr($this->log_file, self::LOG_FILE_PREFIX) === false) {
      throw new Exception(E::ts("Illegal log file path requested"));
    }
  }

  public function buildQuickForm() {
    $this->setTitle(E::ts("Anonymisation Log"));
    $this->return_url = CRM_Utils_Request::retrieve('return_url', 'String', $this);
    $this->log_file = CRM_Utils_Request::retrieve('log_file', 'String', $this);

    // add log data
    $this->verifyLogFile();
    $log = file_get_contents($this->log_file);
    $this->assign('log', $log);

    $this->addButtons(
        [
            [
                'type' => 'submit',
                'name' => E::ts('Download'),
                'icon' => 'fa-download',
                'isDefault' => true,
            ],
            [
                'type' => 'done',
                'name' => E::ts('Done'),
                'isDefault' => false,
            ],
        ]
    );

    parent::buildQuickForm();
  }

  public function postProcess()
  {
    // this means somebody clicked download
    $vars = $this->exportValues();
    if (isset($vars['_qf_LogViewer_submit'])) {
      // download the log file
      $this->verifyLogFile();
      $log_content = file_get_contents($this->log_file);
      CRM_Utils_System::download(
          E::ts("Anonymisation %1.txt", [1 => date('Y-m-d')]),
          'text/plain',
          $log_content
      );
    } else if (isset($vars['_qf_LogViewer_done'])) {
      // go back
      CRM_Utils_System::redirect(base64_decode($this->return_url));
    }

    parent::postProcess();
  }
}
