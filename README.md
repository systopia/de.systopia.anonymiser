# SYSTOPIA Contact Anonymiser

This extension will allow you to anonymise a contact in your database without losing its statistical data.

Once installed, this extension provides an 'Anonymise Contact' summary action to an administrator, that will anonymise any statistically relevant data, and clean out all other - including the log tables.

## Features
 * Anonymises contact base data, contribution, membership and event participation information
 * Removes all other information related to the contact or the above entities
 * Removes all information from log and extended log
 
## Installation

 1. Simply download and install this extension
 2. Make sure that your Admin user has all permissions. That includes the ones for modules that you might not even have enabled (like CiviEvent or CiviMember). Otherwise you'll get errrors during the anonymisation process.
 3. If you are using extended logging, there is a slight complication. The database engine used there (ARCHIVE) doens't allow you to delete entries. You will have to patch (see /patches folder) your CiviCRM before you enable extended logging, so the log_ tables will be created with the right engine (InnoDB). If you already have log tables, you can either drop them, or convert them yourself.

