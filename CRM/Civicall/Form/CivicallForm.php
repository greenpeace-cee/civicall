<?php

use CRM_Civicall_ExtensionUtil as E;

abstract class CRM_Civicall_Form_CivicallForm extends CRM_Core_Form {

  public function preProcess() {
    // TODO make separate compiling and including css and js
    CRM_Core_Resources::singleton()->addScriptFile('civicall', 'js/civicall.js', 1000, 'html-header');
    parent::preProcess();
  }

}
