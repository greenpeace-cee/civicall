<?php
use CRM_Civicall_ExtensionUtil as E;

class CRM_Civicall_Page_CivicallError extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('Civicall Error'));
    $errorMessage = CRM_Utils_Request::retrieve('error-message', 'String', $this);
    $this->assign('errorMessage', urldecode($errorMessage));
    parent::run();
  }

}
