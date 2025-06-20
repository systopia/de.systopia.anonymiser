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

class CRM_Anonymiser_Form_Task_Anonymise extends CRM_Contact_Form_Task
{
  /** @var int number of contacts to be anonymised per queue item */
  const BATCH_SIZE = 10;

  public function buildQuickForm()
  {
    parent::buildQuickForm();
    $this->setTitle(E::ts("Anonymise %1 Contacts", [1 => count($this->_contactIds)]));
  }


  public function postProcess()
  {
    parent::postProcess();

    // create a runner queue
    $queue = CRM_Queue_Service::singleton()->create(
        [
            'type'  => 'Sql',
            'name'  => 'anonymisation_' . CRM_Core_Session::singleton()->getLoggedInContactID(),
            'reset' => true,
        ]
    );

    // create a log file
    $log_file = tempnam(sys_get_temp_dir(), CRM_Anonymiser_Form_LogViewer::LOG_FILE_PREFIX);
    file_put_contents($log_file,
                      E::ts("Anonymisation Run %1", [1 => date('Y-m-d H:i:s')]).
                      "\n================================================\n");

    // fill the runner queue
    $current_batch_contact_ids = [];
    $contact_count = count($this->_contactIds);
    $current_offset = 0;

    // now create an item for each
    foreach ($this->_contactIds as $contact_id) {
      $current_batch_contact_ids[] = (int) $contact_id;
      if (count($current_batch_contact_ids) == self::BATCH_SIZE) {
        $queue->createItem(
            new CRM_Anonymiser_AnonymiserJob(
                $current_batch_contact_ids,
                E::ts("Anonymising contacts %1 - %2", [
                    1 => $current_offset + 1,
                    2 => $current_offset + self::BATCH_SIZE]),
                $log_file
            )
        );
        $current_offset += self::BATCH_SIZE;
        $current_batch_contact_ids = [];
      }
    }
    if (count($current_batch_contact_ids) > 0) {
      $queue->createItem(
        new CRM_Anonymiser_AnonymiserJob(
            $current_batch_contact_ids,
            E::ts("Anonymising contacts %1 - %2", [
                1 => $current_offset + 1,
                2 => $current_offset + count($current_batch_contact_ids)]),
            $log_file
        )
      );
    }

    // create the link to the download screen
    $return_link = base64_encode(CRM_Core_Session::singleton()->readUserContext());
    $log_link = CRM_Utils_System::url('civicrm/contact/anonymise/log', "log_file={$log_file}&return_url={$return_link}");
    $runner = new CRM_Queue_Runner(
        [
            'title'     => E::ts("Anonymising %1 contacts...", [1 => $contact_count]),
            'queue'     => $queue,
            'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
            'onEndUrl'  => $log_link,
        ]
    );

    $runner->runAllViaWeb();
  }

  private function getMessageTemplates(): array
  {
    $list  = [];
    $query = civicrm_api3(
        'MessageTemplate',
        'get',
        [
            'is_active'    => 1,
            'workflow_id'  => ['IS NULL' => 1],
            'option.limit' => 0,
            'return'       => 'id,msg_title',
        ]
    );

    foreach ($query['values'] as $status) {
      $list[$status['id']] = $status['msg_title'];
    }

    return $list;
  }

  private function getParticipantRoles(): array
  {
    $list  = [];
    $query = civicrm_api3(
        'OptionValue',
        'get',
        [
            'option_group_id' => 'participant_role',
            'option.limit'    => 0,
            'return'          => 'value,label',
        ]
    );

    foreach ($query['values'] as $role) {
      $list[$role['value']] = $role['label'];
    }

    return $list;
  }
}
