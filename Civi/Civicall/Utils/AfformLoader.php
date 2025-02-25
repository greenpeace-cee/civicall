<?php

namespace Civi\Civicall\Utils;

use Civi;

class AfformLoader {

  public $name = '';
  public $afform = null;
  public $variables = [];

  /**
   * @param string $name
   */
  public function __construct(string $name, $variables = []) {
    $this->name = $name;
    $this->variables = $variables;
    $this->loadAfform();
  }

  private function loadAfform() {
    if (empty($this->name)) {
      return;
    }

    $afform = civicrm_api4('Afform', 'get', [
      'select' => ['directive_name', 'module_name'],
      'where' => [['name', '=', $this->name]],
    ])->first();

    if (empty($afform)) {
      return;
    }

    $this->afform = $afform;
  }

  public function isAfformExist() {
    if (empty($this->afform)) {
      return false;
    }

    return true;
  }

  /**
   * @return string
   */
  public function getTemplate() {
    if (empty($this->afform)) {
      return 'Cannot load Afform Module: "' . $this->name . '"';
    }

    $encodedVariables = json_encode($this->variables);

    return "
      <crm-angular-js modules='{$this->afform['module_name']}'>
        <div id='bootstrap-theme' class='{$this->afform['module_name']}'>
          <{$this->afform['directive_name']} options='{$encodedVariables}'></{$this->afform['directive_name']}>
        </div>
      </crm-angular-js>
    ";
  }

  public function loadAngularjsModule() {
    if (empty($this->afform)) {
      return;
    }

    Civi::service('angularjs.loader')->addModules($this->name);
  }

}
