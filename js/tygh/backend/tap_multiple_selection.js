/**
 * Enable multiple selection in admin.
 */
(function (_, $) {
  // select an object if it has already been selected
  var reSelect = function reSelect(longtap, $container) {
    $container = $container ? $container : $('[data-ca-longtap]');
    $container.each(function () {
      var $self = $(this);
      longtap = longtap ? longtap : $self.data('caLongtap');

      if (!longtap) {
        return;
      }

      $('[data-ca-longtap-action]', $self).each(function (index, item) {
        var $self = $(item);

        if ($self.data().caLongtapAction == 'setCheckBox') {
          var checkboxSelector = $self.data().caLongtapTarget;
          var $checkbox = $self.find(checkboxSelector);
          var checked = $checkbox.prop('checked');

          if (checked) {
            $self.removeClass('selected');
            longtap.selectObject(index);
          } else {
            if ($self.hasClass('selected')) {
              longtap.rejectObject(index);
            }
          }

          $checkbox.on('change', function (event) {
            if ($checkbox.prop('checked')) {
              longtap.storage.elements[index].handlersSuccess.select(event);
            } else {
              if ($self.hasClass('selected')) {
                longtap.storage.elements[index].handlersSuccess.reject(event);
              }
            }
          });
        }
      });
      $.ceEvent('trigger', 'ce.tap.toggle', [longtap.storage.selected]);
    });
  };

  $.ceEvent('on', 'ce.commoninit', function (context) {
    if (!$(context).find('[data-ca-bulkedit-component]').length) {
      return;
    }

    function setCheckboxFlag(selfObj, targetSelector, flag) {
      selfObj.find(targetSelector).each(function (index, elm) {
        elm.checked = flag;
      });
    }

    function _checkSelected(selfObj) {
      return selfObj.hasClass('selected');
    }

    function _checkDisableSelected(selfObj) {
      return selfObj.hasClass('longtap-selection-disable');
    } // initialize plugin


    $('[data-ca-longtap]').each(function () {
      var $container = $(this);
      var longtap = $('[data-ca-longtap-action]', $container).ceTap({
        timeout: 700,
        onStartDelay: 250,
        allowQuickMode: true,
        mouseMultipleSelection: true,
        preSuccess: function preSuccess(event, self) {
          var isCheck = _checkSelected($(self)) || _checkDisableSelected($(self)),
              disableSelectedNotice = $(self).data('caBulkeditDisabledNotice');

          if (isCheck && disableSelectedNotice) {
            $.ceNotification('show', {
              title: _.tr('warning'),
              message: disableSelectedNotice,
              type: 'W'
            });
          }

          return isCheck;
        },
        preReject: function preReject(event, self) {
          return !_checkSelected($(self));
        },
        onStart: function onStart(event, self) {
          self.addClass('long-tap-start');
        },
        onSuccess: function onSuccess(event, self) {
          self.removeClass('long-tap-start');
          self.addClass('selected');

          if (self.data().caLongtapAction == 'setCheckBox') {
            setCheckboxFlag(self, self.data().caLongtapTarget, true);
            $container.find('[data-ca-longtap-selected-counter=true]').text(longtap.storage.selected);
            $.ceEvent('trigger', 'ce.tap.toggle', [longtap.storage.selected, $container]);
          }
        },
        onStop: function onStop(event, self) {
          self.removeClass('long-tap-start');
        },
        onReject: function onReject(event, self) {
          self.removeClass('long-tap-start');
          self.removeClass('selected');

          if (self.data().caLongtapAction == 'setCheckBox') {
            setCheckboxFlag(self, self.data().caLongtapTarget, false);
            $container.find('[data-ca-longtap-selected-counter=true]').text(longtap.storage.selected);
            $.ceEvent('trigger', 'ce.tap.toggle', [longtap.storage.selected, $container]);
          }
        }
      });
      $container.data('caLongtap', longtap);
      reSelect(longtap, $container);
    });
  });
  $.ceEvent('on', 'ce.cm_cancel.clean_form', function ($form, $jelm) {
    if ($jelm.hasClass('bulkedit-unchanged')) {
      return;
    }

    reSelect();
  });
  $(_.doc).on('click', '.bulkedit-deselect', function () {
    var $form = $(this).closest('form');

    if ($form.find('[data-ca-bulkedit-component]').length) {
      $form.find('[data-ca-longtap-action] input[type="checkbox"]').prop('checked', false);
      reSelect();
    }
  });
})(Tygh, Tygh.$);