<?php
/**
 * Copyright (C) 2022  Jaap Jansma (jaap.jansma@civicoop.org)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

use CRM_Anonymiser_ExtensionUtil as E;

return [
  'anonymiser_groups' => [
    'settings_pages' => ['anonymiser' => ['weight' => 1]],
    'group_name' => E::ts('Anonymiser'),
    'group' => 'anonymiser',
    'name' => 'anonymiser_groups',
    'type' => 'String',
    'options' => [
      '0' => E::ts('Keep group membership state'),
      '1' => E::ts('Remove group state from contact'),
    ],
    'default' => '0',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2 huge',
    ],
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Groups'),
  ],
  'anonymiser_tags' => [
    'settings_pages' => ['anonymiser' => ['weight' => 1]],
    'group_name' => E::ts('Anonymiser'),
    'group' => 'anonymiser',
    'name' => 'anonymiser_tags',
    'type' => 'String',
    'options' => [
      '0' => E::ts('Keep tags'),
      '1' => E::ts('Remove tags from contact'),
    ],
    'default' => '0',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2 huge',
    ],
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Tags'),
  ],
  'anonymiser_contact_dates' => [
    'settings_pages' => ['anonymiser' => ['weight' => 1]],
    'group_name' => E::ts('Anonymiser'),
    'group' => 'anonymiser',
    'name' => 'anonymiser_contact_dates',
    'type' => 'String',
    'options' => [
      '0' => E::ts('Keep contact dates'),
      '1' => E::ts('Remove dates'),
    ],
    'default' => '1',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2 huge',
    ],
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Contact dates'),
    'description' => E::ts('Contact dates are birth date, deceased date and created date. When you keep the dates they will be set to the 1st of January of that year.'),
  ],
  'anonymiser_contributions' => [
    'settings_pages' => ['anonymiser' => ['weight' => 1]],
    'group_name' => E::ts('Anonymiser'),
    'group' => 'anonymiser',
    'name' => 'anonymiser_contributions',
    'type' => 'String',
    'options' => [
      '0' => E::ts('Keep contributions and anonymise them'),
      '1' => E::ts('Delete contribution records'),
    ],
    'default' => '0',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2 huge',
    ],
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Contributions'),
  ],
  'anonymiser_participants' => [
    'settings_pages' => ['anonymiser' => ['weight' => 1]],
    'group_name' => E::ts('Anonymiser'),
    'group' => 'anonymiser',
    'name' => 'anonymiser_participants',
    'type' => 'String',
    'options' => [
      '0' => E::ts('Keep participant records and anonymise them'),
      '1' => E::ts('Delete participant records'),
    ],
    'default' => '0',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2 huge',
    ],
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Participants'),
  ],
  'anonymiser_memberships' => [
    'settings_pages' => ['anonymiser' => ['weight' => 1]],
    'group_name' => E::ts('Anonymiser'),
    'group' => 'anonymiser',
    'name' => 'anonymiser_memberships',
    'type' => 'String',
    'options' => [
      '0' => E::ts('Keep memberships and anonymise them'),
      '1' => E::ts('Delete membership records'),
    ],
    'default' => '0',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2 huge',
    ],
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Memberships'),
  ],
];
