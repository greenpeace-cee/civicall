<?php

namespace Civi\Civicall\HookListeners\Links;

use Civi\Civicall\Utils\CivicallUtils;
use Civi\Core\Event\GenericHookEvent;
use CRM_Core_Permission;

class ApplyCallCenterLinks {

  /**
   * @param GenericHookEvent $event
   */
  public static function run(GenericHookEvent $event) {
    if ($event->objectName !== 'Activity') {
      return;
    }

    if (!in_array($event->op, ['activity.tab.row', 'activity.selector.row'])) {
      return;
    }

    $event->links = self::removeEditRawActivityLinks($event->links);

    $event->links[] = [
      'name' => 'Edit Call',
      'title' => 'Edit Call',
      'qs' => 'reset=1&activity_id=%%id%%',
      'url' => 'civicrm/civicall/call-center',
    ];
  }

  private static function removeEditRawActivityLinks($links): array {
    $preparedLinks = [];

    foreach ($links as $link) {
      if (!CRM_Core_Permission::check('administer CiviCRM') && $link['name'] == 'Edit') {
        if (CivicallUtils::isStringContains('civicrm/fastactivity/add', $link['url'])
          || CivicallUtils::isStringContains('civicrm/activity/add', $link['url']) ) {
          continue;
        }
      }

      $preparedLinks[] = $link;
    }

    return $preparedLinks;
  }

}
