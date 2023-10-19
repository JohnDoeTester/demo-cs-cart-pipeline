(function ($) {
  function generateUniqueId(length, prefix) {
    if (typeof prefix === 'undefined') {
      prefix = 'uid_';
    }

    var result = prefix;
    var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;

    for (var i = 0; i < length; i++) {
      result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }

    return result;
  }

  $.fn.extend({
    /**
     * Calculate actually unique selector
     * @returns {string} selector
     */
    getPath: function getPath() {
      var uniqueElementId;

      if ($(this).prop('id')) {
        uniqueElementId = $(this).prop('id');
      } else {
        do {
          uniqueElementId = generateUniqueId(16);
        } while ($('#' + uniqueElementId).length !== 0);

        $(this).prop('id', uniqueElementId);
      }

      return '#' + uniqueElementId;
    }
  });

  $.fn.ceTap = function (args) {
    var plugin = function plugin() {
      var pluginInstanceStorage = {
        selected: 0,
        quickMode: false,
        allowQuickMode: args.allowQuickMode || true,
        mouseMultipleSelection: args.mouseMultipleSelection || false,
        elements: [],
        currentPointer: ''
      };
      var preventClick = args.preventClick || false;
      var preventSelect = args.preventSelect || true;
      var preventContext = args.preventContext || true;

      var calcPosition = function calcPosition(pointer) {
        var _index = undefined;
        pluginInstanceStorage.elements.forEach(function (item, index) {
          _index = item.pointer == pointer ? index : _index;
        });
        return _index;
      };

      var eachFunction = function eachFunction(index, self) {
        var $self = $(self);
        $self.storage = pluginInstanceStorage;
        var timer = undefined;
        var startTimer = undefined;
        var isSelected = false;
        var isPreStart = false;
        var isTapStart = false;
        var isTapStartStamp = 0;
        var shouldIPreventClick = false;

        var clearingTimers = function clearingTimers(variable, clearCallback, event, stopFlag) {
          if (!!variable) {
            clearCallback();

            if (args.onStop) {
              if (stopFlag) {
                args.onStop(event, $self);
              }
            }
          }
        };

        var timeouts = {
          mainDelay: args.timeout || 1000,
          mainDelayClear: function mainDelayClear() {
            clearTimeout(timer);
            timer = undefined;
          },
          onStartDelay: args.onStartDelay || 10,
          onStartDelayClear: function onStartDelayClear() {
            clearTimeout(startTimer);
            startTimer = undefined;
          }
        };
        var handlersSuccess = {
          select: function select(event) {
            // additional check before selecting
            if (args.preSuccess) {
              if (args.preSuccess(event, $self)) {
                return;
              }
            }

            isSelected = !isSelected;
            pluginInstanceStorage.selected++;
            pluginInstanceStorage.currentPointer = $self.getPath();
            pluginInstanceStorage.elements[calcPosition($self.getPath())].selected = true;

            if (args.onSuccess) {
              args.onSuccess(event, $self);
            }

            if (pluginInstanceStorage.allowQuickMode) {
              if (pluginInstanceStorage.selected > 0) {
                pluginInstanceStorage.quickMode = true;
              }
            }
          },
          reject: function reject(event) {
            // additional check before rejecting
            if (args.preReject) {
              if (args.preReject(event, $self)) {
                return;
              }
            }

            isSelected = !isSelected;
            pluginInstanceStorage.selected--;
            pluginInstanceStorage.currentPointer = $self.getPath();
            pluginInstanceStorage.elements[calcPosition($self.getPath())].selected = false;

            if (args.onReject) {
              args.onReject(event, $self);
            }

            if (pluginInstanceStorage.selected == 0) {
              pluginInstanceStorage.quickMode = false;
            }
          }
        };
        var handlers = {
          success: function success(event, forceReject, mouseMultipleSelectionParams) {
            var oldPointer = '';
            var newPointer = '';
            var oldPosition = 0;
            var newPosition = 0;
            var selectMode = false; // save previous value of previously selected item 'position in list'

            if (mouseMultipleSelectionParams) {
              oldPointer = pluginInstanceStorage.currentPointer;
              oldPosition = calcPosition(oldPointer);
              newPointer = mouseMultipleSelectionParams.sel;
              newPosition = calcPosition(newPointer);
              selectMode = !pluginInstanceStorage.elements[newPosition].selected;
              forceReject = !selectMode;
            }

            if (isSelected || forceReject) {
              handlersSuccess.reject(event);
            } else {
              handlersSuccess.select(event);
            }

            isPreStart = false;
            timeouts.mainDelayClear(); // additional logic for multiple selecting

            if (mouseMultipleSelectionParams) {
              if (!mouseMultipleSelectionParams.shiftKey) {
                return;
              } // non-handling behavior, just ignore


              if (oldPosition == undefined || newPosition == undefined) {
                return;
              } // calculate all blocks that must be selected
              // and [un]select them


              var delta = oldPosition < newPosition ? oldPosition : newPosition;
              var elmStorage = $self.storage.elements;

              for (var i = 0; i <= Math.abs(oldPosition - newPosition); i++) {
                var _elmStorage = elmStorage[i + delta];

                if (selectMode) {
                  // normal logic, select all unselected
                  _elmStorage.handlersSuccess.select(event);
                } else {
                  // inverse logic, unselect all selected
                  _elmStorage.handlersSuccess.reject(event);
                }
              } // force saving current position


              pluginInstanceStorage.currentPointer = newPointer;
            }
          },
          stop: function stop(event) {
            clearingTimers(startTimer, timeouts.onStartDelayClear, event);
            clearingTimers(timer, timeouts.mainDelayClear, event, true);
          },
          override: function override(callback, preventFlag) {
            return function (event) {
              if (callback) {
                callback(event, $self);
              }

              if (preventFlag) {
                events.killEvent(event);
              }
            };
          }
        };
        var events = {
          killEvent: function killEvent(event) {
            event.preventDefault();
            return;
          },
          scrolling: function scrolling(event) {
            if (isPreStart == false) {
              handlers.stop(event);
            }

            if (isTapStart) {
              isTapStart = false;
            }
          },
          tapStart: function tapStart(event) {
            isTapStart = true;
            isTapStartStamp = performance.now();
            var focusableElements = $self.find(':focusable, .cm-external-click, a:not([href]):visible');
            var eventTarget = $(event.target);
            var eventTargetParent = eventTarget.parent();

            if (eventTarget.find('i[class*=icon]') && eventTargetParent.is('a, button')) {
              shouldIPreventClick = focusableElements.is(eventTargetParent);
            } else {
              shouldIPreventClick = focusableElements.is(eventTarget);
            } // if `shouldIPreventClick == true` this timeout will be cleaned in `tapEnd` event


            startTimer = setTimeout(function () {
              event.preventDefault();
              isPreStart = true;

              if (args.onStart) {
                args.onStart(event, $self);
              }

              timer = setTimeout(handlers.success, timeouts.mainDelay, event);
              timeouts.onStartDelayClear();
            }, timeouts.onStartDelay);
          },
          tapEnd: function tapEnd(event) {
            if (isTapStart) {
              isTapStart = false;

              if (performance.now() - isTapStartStamp <= 300) {
                if (shouldIPreventClick) {
                  handlers.stop(event);
                  return;
                }

                if (pluginInstanceStorage.quickMode) {
                  handlers.success(event);
                }
              }
            }

            if (event.cancelable) {
              event.preventDefault();
            }

            handlers.stop(event);
          }
        };
        $self.storage.elements.push({
          timeouts: timeouts,
          handlers: handlers,
          handlersSuccess: handlersSuccess,
          events: events,
          pointer: $self.getPath(),
          selected: false
        });

        if (Modernizr.touchevents) {
          $self.off('contextmenu.tap').on('contextmenu.tap', handlers.override(args.onContext, preventContext));
          $self.off('selectstart.tap').on('selectstart.tap', handlers.override(args.onSelect, preventSelect));
        }

        $self.off('touchmove.tap').on('touchmove.tap', events.scrolling);
        $self.off('touchstart.tap').on('touchstart.tap', events.tapStart);
        $self.off('touchend.tap').on('touchend.tap', events.tapEnd); // force mouse selecting, else prevent

        if ($self.storage.mouseMultipleSelection) {
          $self.off('click.tap').on('click.tap', function (e) {
            if ($(e.target).is(':focusable') || $(e.target).is('a, button') || $(e.target).parents().is('a, button')) {
              return;
            }

            handlers.success(e, false, {
              shiftKey: e.shiftKey,
              data: $self.data(),
              sel: $self.getPath()
            });
          });
        } else {
          $self.off('click.tap').on('click.tap', handlers.override(args.onClick, preventClick));
        }
      };

      return {
        each: eachFunction,
        storage: pluginInstanceStorage,
        selectObject: function selectObject(index, reverse) {
          pluginInstanceStorage.elements[index].handlers.success();
        },
        rejectObject: function rejectObject(index, reverse) {
          // force rejecting
          pluginInstanceStorage.elements[index].handlers.success(true);
        }
      };
    };

    var ceTap = plugin();
    this.each(ceTap.each);
    return ceTap;
  };
})(jQuery);