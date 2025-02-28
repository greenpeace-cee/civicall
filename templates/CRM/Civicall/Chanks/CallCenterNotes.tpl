<div class="notes__wrap" id="{$callCenterNotesWrapId}" data-activity-id="{$activity.id}">
  <div class="notes__header">
    <div class="notes__title">Notes</div>
    <div class="notes__auto-save-status">
      <div class="notes__status-icon notes__status-icon-saved" title="saved">
        <i aria-hidden="true" class="crm-i fa-check"></i>
      </div>
      <div class="notes__status-icon notes__status-icon-loading" title="saving...">
        <i aria-hidden="true" class="crm-i fa-floppy-o"></i>
      </div>
      <div class="notes__status-icon notes__status-icon-unsaved" title="last changes is unsaved">
        <i aria-hidden="true" class="crm-i fa-circle"></i>
      </div>
    </div>
  </div>

  <div class="notes__textarea-wrap">
    {$form.notes.html}
  </div>
</div>

{literal}
<script>
  (function (CRM, $) {
    $(document).ready(function() {
      var notesWrapIdSelector = '#{/literal}{$callCenterNotesWrapId}{literal}';
      var notesWrap = $(notesWrapIdSelector);
      var textareaElement = notesWrap.find('.notes__textarea-wrap textarea#notes');
      var currentValue = textareaElement.val();
      var serverValue = textareaElement.val();

      initNotesAutoSave();

      function initNotesAutoSave() {
        switchAutoSaveStatus('saved');

        civicallSetInterval(notesWrapIdSelector, function () {
          // Prevents saving when popup with page is closed
          if ($(notesWrapIdSelector).length !== 1) {
            return;
          }

          currentValue = textareaElement.val();
          if (serverValue === currentValue) {
            return;
          }

          switchAutoSaveStatus('loading');

          CRM.api4('Activity', 'update', {
            values: {"details": currentValue},
            where: [["id", "=", notesWrap.data('activity-id')]]
          }).then(function(results) {
            serverValue = currentValue;
            switchAutoSaveStatus('saved');
          }, function(failure) {
            console.error('Error while updating notes!');
          });
        }, 10000, 10);

        textareaElement.on('change keyup paste', function() {
          if (serverValue !== textareaElement.val()) {
            switchAutoSaveStatus('unsaved');
          }
        });
      }

      function switchAutoSaveStatus(status) {
        var statusElement = notesWrap.find('.notes__auto-save-status');

        if (status === 'loading') {
          statusElement.addClass('notes--loading');
          statusElement.removeClass('notes--unsaved');
        }

        if (status === 'unsaved') {
          statusElement.removeClass('notes--loading');
          statusElement.addClass('notes--unsaved');
        }

        if (status === 'saved') {
          statusElement.removeClass('notes--loading');
          statusElement.addClass('notes--saved');
        }
      }

      // Wrap for setInterval loop
      // Clears interval when popup with this page is closed.
      // It checks every some iteration if exist target element.
      function civicallSetInterval(elementSelector, callback, interval, checkEveryIteration) {
        if ($(elementSelector).length === 0) {
          return;
        }

        var iteration = 0;
        var intervalId = setInterval(function () {
          if ((iteration % checkEveryIteration === 0) && $(elementSelector).length === 0) {
            clearInterval(intervalId);
          }
          callback();
          iteration++;
        }, interval);
      }

    });
  })(CRM, CRM.$);
</script>
{/literal}
