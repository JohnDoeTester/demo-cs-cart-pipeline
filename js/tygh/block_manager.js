function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }

// Class Block manager Menu
function BlockManager_Class() {
  // Private variables
  var _event = {};
  var _hover_element = {};
  var _init_params = {};

  var _self = this;

  var $ = Tygh.$;
  var _ = Tygh; // Public variables

  this.menu_type = ''; //container, grid, block

  this.menu_status = 'H'; // "H"idden; "D"isplayed;

  this.inited = false; // Private functions

  var _setEvent = function _setEvent(event) {
    _event = event;
  };

  var _determineElementType = function _determineElementType(element) {
    element = element || _hover_element;
    var type = '';

    if (element.hasClass(_init_params.container_class)) {
      type = 'container';
    } else if (element.hasClass(_init_params.grid_class)) {
      type = 'grid';
    } else {
      type = 'block';
    }

    return {
      type: type,
      id: element.prop('id')
    };
  };

  this.setAvailability = function (availability) {
    availability = availability || [];
    $.each(availability, function (i, container) {
      var all_available = true;
      $.each(container.availability, function (device, is_availabe) {
        all_available = all_available && is_availabe;
      });
      $.each(container.availability, function (device, is_available) {
        $('#' + container.type + '_' + container.id + ' > .grid-control-menu .device-specific-block__devices__device--' + device).toggleClass('hidden', all_available || !is_available);
      });
    });
    this.calculateLevels();
  };

  var _parseResponse = function _parseResponse(data, params) {
    // If we received "id" - apply this id to new element
    if (typeof data.id !== 'undefined') {
      var new_id = '';

      if (data.mode === 'grid') {
        new_id = 'grid_' + data.id;
      } else if (data.mode === 'snapping') {
        new_id = 'snapping_' + data.id;
      }

      var elm = $('#new_element').prop('id', new_id);

      _self.setBlockManagerActions(elm, data);

      if (data.availability) {
        _self.setAvailability(data.availability);
      }
    }

    _self.calculateLevels();
  };

  var _snapBlocks = function _snapBlocks(block) {
    var snapping = {};
    var blocks = block.parent().find('.' + _init_params.block_class);
    blocks.each(function () {
      var _block = $(this);

      var id = _block.index();

      snapping[id] = {};
      snapping[id].grid_id = _block.parent().prop('id').replace('grid_', '');
      snapping[id].order = _block.index();

      if (_block.hasClass('base-block')) {
        snapping[id].block_id = _block.prop('id').replace('block_', '');
        snapping[id].action = 'add';

        _block.prop('id', 'new_element');

        _block.removeClass('base-block');
      } else {
        snapping[id].snapping_id = _block.prop('id').replace('snapping_', '');
        snapping[id].action = 'update';
      }
    });

    _self.sendRequest('snapping', '', {
      snappings: snapping
    });
  };

  var _executeAction = function _executeAction(action) {
    // Init local variables
    var container_title, container, prop_container, href, max_width, container_id; // Determine element (container, grid, block)

    var element_type = _determineElementType().type;

    var execute_result = false; // Hide element control menu and execute "action"

    _hover_element.parent().find('.cm-popup-box').hide();

    switch (action) {
      case 'properties':
        if (element_type == 'block') {
          href = 'block_manager.update_block?';
          href += 'snapping_data[snapping_id]=' + _hover_element.prop('id').replace('snapping_', '');
          href += '&content_data[grid_id]=' + _hover_element.parent().prop('id').replace('grid_', '');
          href += '&selected_location=' + (typeof selected_location == 'undefined' ? 0 : selected_location);
          href += '&dynamic_object[object_type]=' + (typeof dynamic_object_type == 'undefined' ? '' : dynamic_object_type);
          href += '&dynamic_object[object_id]=' + (typeof dynamic_object_id == 'undefined' ? 0 : dynamic_object_id);
          href += '&s_layout=' + $('#s_layout').val();
          prop_container = 'prop_' + _hover_element.prop('id');

          if ($('#' + prop_container).length == 0) {
            // Create properties container
            container_title = _hover_element.data('caBlockName').toString() || Tygh.tr('editing_block');
            container = $('<div id="' + prop_container + '" title="' + _escape(container_title) + '"></div>').appendTo('body');
          }
        } else if (element_type == 'grid') {
          var _max_width = _self.getMaxWidth(),
              _min_width = _self.getMinWidth(),
              max_offset = _self.getMaxOffset();

          prop_container = 'grid_properties_' + _hover_element.prop('id').replace('grid_', '');
          href = 'block_manager.update_grid?' + 'grid_data[grid_id]=' + _hover_element.prop('id').replace('grid_', '');
          href += '&grid_data[max_width]=' + _max_width;
          href += '&grid_data[min_width]=' + _min_width;
          href += '&grid_data[container_id]=' + _hover_element.parents('.container').prop('id').replace('container_', '');
          href += '&grid_data[max_offset]=' + max_offset;

          if ($('#' + prop_container).length == 0) {
            // Create properties container
            container = $('<div id="' + prop_container + '" title="' + _escape(Tygh.tr('editing_grid')) + '"></div>').appendTo('body');
          }
        } else if (element_type == 'container') {
          container_title = _hover_element.find('> .grid-control-menu > .grid-control-title').text();
          href = 'block_manager.update_container?';
          href += '&container_id=' + _hover_element.prop('id').replace('container_', '');
          prop_container = 'container_properties_' + _hover_element.prop('id').replace('container_', '');

          if ($('#' + prop_container).length == 0) {
            // Create properties container
            container = $('<div id="' + prop_container + '" title="' + container_title + '"></div>').appendTo('body');
          }
        }

        var currentScrollPosition = $(document).scrollTop();
        $('#' + prop_container).ceDialog('open', {
          href: fn_url(href)
        });
        $.ceEvent('on', 'ce.dialogclose', function () {
          $('body,html').scrollTop(currentScrollPosition);
        });
        break;

      case 'add-grid':
        var max_width = _self.getMaxWidth(null, true);

        var min_width = _self.getMinWidth();

        prop_container = 'grid_properties_new';
        href = 'block_manager.update_grid?';
        href += 'grid_data[max_width]=' + max_width;
        href += 'grid_data[min_width]=' + min_width;

        if (element_type == 'container') {
          container_id = _hover_element.prop('id').replace('container_', '');
          href += '&grid_data[container_id]=' + container_id;
          href += '&grid_data[parent_id]=0';
          prop_container += '_' + container_id + '_0';
        } else {
          container_id = _hover_element.parents('.container').prop('id').replace('container_', '');

          var parent_id = _hover_element.prop('id').replace('grid_', '');

          href += '&grid_data[container_id]=' + container_id;
          href += '&grid_data[parent_id]=' + parent_id;
          prop_container += '_' + container_id + '_' + parent_id;
        }

        if ($('#' + prop_container).length == 0) {
          // Create properties container
          container = $('<div id="' + prop_container + '" title="' + _escape(Tygh.tr('adding_grid')) + '"></div>').appendTo('body');
        }

        $('#' + prop_container).ceDialog('open', {
          href: fn_url(href)
        });
        break;

      case 'add-block':
        href = 'block_manager.block_selection?';
        href += '&grid_id=' + _hover_element.prop('id').replace('grid_', '');
        href += '&selected_location=' + (typeof selected_location == 'undefined' ? 0 : selected_location);
        href += '&s_layout=' + $('#s_layout').val();
        container = $('#block_selection');

        if (!container.length) {
          container = $('<div id="block_selection" title="' + _escape(Tygh.tr('adding_block_to_grid')) + '"></div>').appendTo('body');
        }

        container.ceDialog('open', {
          href: fn_url(href)
        });
        break;

      case 'delete':
        if (confirm(Tygh.tr('text_are_you_sure_to_proceed')) != false) {
          if (element_type == 'grid') {
            var data = {
              snappings: _self.deleteStructure(_hover_element)
            };

            _self.sendRequest('grid', 'update', data);
          } else if (element_type == 'block') {
            var data = {
              snappings: _self.deleteStructure(_hover_element)
            };

            _self.sendRequest('snapping', 'update', data);

            if ($('.bm-block-single-for-location', _hover_element).length) {
              $('#block_selection').dialog('destroy').remove();
            }
          }
        }

        break;

      case 'manage-blocks':
        href = 'block_manager.block_selection?manage=Y';
        container = $('#block_managing');

        if (!container.length) {
          container = $('<div id="block_managing" title="' + _escape(Tygh.tr('manage_blocks')) + '"></div>').appendTo('body');
        }

        $('#block_managing').ceDialog('open', {
          href: fn_url(href)
        });
        break;

      case 'switch':
        if (element_type == 'block') {
          var button = $('.bm-action-switch', _hover_element);
          var status = button.hasClass('switch-off') ? 'A' : 'D';
          var dynamic_object = button.hasClass('bm-dynamic-object') ? button.data('caBmObjectId') : 0;

          if (button.hasClass('bm-confirm')) {
            var text = button.find(".confirm-message").text();

            if (text == "" || text == 'undefined') {
              text = Tygh.tr('text_are_you_sure_to_proceed');
            }

            if (confirm(text) == false) {
              return false;
            } else {
              button.removeClass("bm-confirm");
            }
          }

          var data = {
            snapping_id: _hover_element.prop('id').replace('snapping_', ''),
            object_id: dynamic_object,
            object_type: dynamic_object_type,
            status: status,
            type: 'block'
          };
          $.ceAjax('request', fn_url('block_manager.update_status'), {
            data: data,
            callback: _parseResponse,
            method: 'post'
          });

          if (status == 'A') {
            button.removeClass('switch-off');

            _hover_element.removeClass('block-off');

            _hover_element.data('caStatus', 'active');
          } else {
            button.addClass('switch-off');

            _hover_element.addClass('block-off');

            _hover_element.data('caStatus', 'disabled');
          }

          execute_result = true;
        } else if (element_type == 'grid') {
          var status = _hover_element.hasClass('grid-off') ? 'A' : 'D';
          var data = {
            grid_id: _hover_element.prop('id').replace('grid_', ''),
            status: status,
            type: 'grid'
          };
          $.ceAjax('request', fn_url('block_manager.update_status'), {
            data: data,
            callback: _parseResponse,
            method: 'post'
          });

          if (status == 'A') {
            _hover_element.removeClass('grid-off');

            _hover_element.data('caStatus', 'active');
          } else {
            _hover_element.addClass('grid-off');

            _hover_element.data('caStatus', 'disabled');
          }

          _self.recheckBlockStatuses(_hover_element);

          execute_result = true;
        } else if (element_type == 'container') {
          var status = _hover_element.hasClass('container-off') ? 'A' : 'D';
          var data = {
            container_id: _hover_element.prop('id').replace('container_', ''),
            status: status,
            type: 'container'
          };
          $.ceAjax('request', fn_url('block_manager.update_status'), {
            data: data,
            callback: _parseResponse,
            method: 'post'
          });

          if (status == 'A') {
            _hover_element.removeClass('container-off');

            _hover_element.data('caStatus', 'active');
          } else {
            _hover_element.addClass('container-off');

            _hover_element.data('caStatus', 'disabled');
          }

          _self.recheckBlockStatuses(_hover_element);

          execute_result = true;
        }

        break;

      case 'control-menu':
        _hover_element.find('> .bm-control-menu .bm-drop-menu').show();

        break;

      default:
        break;
    }

    return execute_result;
  };

  var _escape = function _escape(str) {
    return str.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }; // Public functions


  this.init = function (containers, params) {
    if (this.inited) {
      return true;
    }

    this.inited = true;
    $(containers).disableSelection();
    params.connectWith = '.cm-sortable-grid';
    params.tolerance = 'pointer'; // revert block animation

    params.revert = 100;
    params.device_availability_switcher = params.device_availability_switcher || null;

    params.update = function (event, ui) {
      if (ui.sender == null) {
        _snapBlocks($(ui.item));

        _self.checkMenuItems($(ui.item).parent());
      }

      if (ui.sender) {
        var placeholder = $(this);

        if (placeholder.hasClass('grid-off') || placeholder.parents('.grid-off').length > 0 || placeholder.parents('.container-off').length > 0) {
          ui.item.addClass('block-off');
        } else if (ui.item.data('caStatus') == 'active') {
          ui.item.removeClass('block-off');
        }

        _self.checkMenuItems($(ui.sender));
      }
    };

    params.start = function (event, ui) {
      ui.item.addClass('ui-draggable-block');
    };

    params.beforeStop = function (event, ui) {
      ui.item.removeClass('ui-draggable-block');
    };

    params.stop = function (event, ui) {
      _self.buildMenu(ui.item);
    };

    _init_params = params;
    _init_params.containers = containers;

    _self.initBlockManagerActions(); // Update grid width select


    $(Tygh.doc).on('change', 'select[name="offset"]', function () {
      var $self = $(this),
          gridId = $self.data('caGridId') ? +$self.data('caGridId') : 0;

      if (!gridId) {
        return;
      }

      var $gridElement = $("#".concat(_init_params.grid_class, "_").concat(gridId)),
          $widthGridSelect = $('#elm_grid_width_' + gridId),
          minWidth = _self.getMinWidth($gridElement),
          maxWidth = _self.getMaxWidth($gridElement),
          currentWidth = $widthGridSelect.val() <= maxWidth && $widthGridSelect.val() >= minWidth ? $widthGridSelect.val() : minWidth;

      $widthGridSelect.empty();

      for (var i = minWidth; i <= maxWidth; i += 1) {
        $widthGridSelect.append($('<option></option>').attr('value', i).text(i));
      }

      $widthGridSelect.val(currentWidth);
    }); // Update grid offset select

    $(Tygh.doc).on('change', 'select[name="width"]', function () {
      var $self = $(this),
          gridId = $self.data('caGridId') ? +$self.data('caGridId') : 0;

      if (!gridId) {
        return;
      }

      var $gridElement = $("#".concat(_init_params.grid_class, "_").concat(gridId)),
          $offsetGridSelect = $('#elm_grid_offset_' + gridId),
          maxOffset = _self.getMaxOffset($gridElement, +$self.val()),
          currentOffset = $offsetGridSelect.val() < maxOffset ? $offsetGridSelect.val() : 0;

      $offsetGridSelect.empty();

      for (var i = 0; i < maxOffset; i += 1) {
        $offsetGridSelect.append($('<option></option>').attr('value', i).text(i));
      }

      $offsetGridSelect.val(currentOffset);
    }); // Init sortable zones

    _self.calculateLevels(); // Correct control menues


    _self.checkMenuItems($('.' + params.grid_class, params.parent)); // Disable/Enable blocks in depends on the Grid statuses


    $('.grid-off, .container-off    ').each(function () {
      _self.recheckBlockStatuses($(this));
    });

    if (params.device_availability_switcher) {
      _self.initDeviceAvailabilitySwitcher(params.device_availability_switcher);
    }

    if (params.edit_object_id && params.edit_object_type) {
      _self.openObjectPropertiesForm(params.edit_object_id, params.edit_object_type);
    }
  };

  this.initBlockManagerActions = function (is_manage_blocks, result_id) {
    /*
        We have 2 function to parse actions:
            1) Block manager control elements, like "Add grid", "Properties", etc. 
                Controls by class: cm-action
             2) When we click "Add block" or "Manage blocks". Process clicking on block in a new popup window with blocks. 
                Controls by class: cm-add-block
    */
    $(Tygh.doc).off('click.bm');
    $(Tygh.doc).on('click.bm', '.cm-action', function (e) {
      jelm = $(e.currentTarget).parents('.bm-control-menu').parent();
      _hover_element = jelm;

      _setEvent(e);

      var action = $(e.currentTarget).prop('class').match(/bm-action-([0-9a-zA-Z-]+)/i)[1];
      return _executeAction(action);
    });
    $(Tygh.doc).on('click.bm', '.cm-remove-block', function (e) {
      if (confirm(Tygh.tr('text_are_you_sure_to_proceed')) != false) {
        var block_id = $(this).parent().find('input[name="block_id"]').val();

        _self.sendRequest('block', 'delete', {
          block_id: block_id
        }, is_manage_blocks);

        $(this).parent().remove();
      }

      return false;
    });
    $(Tygh.doc).on('click.bm', '.cm-add-block', function (e) {
      /*
          Adding new block functionality
      */
      var action = $(this).prop('class').match(/bm-action-([a-zA-Z0-9-_]+)/)[1];

      if (action === 'new-block') {
        var is_manage = $(this).hasClass('bm-manage'),
            block_type = $(this).find('input[name="block_data[type]"]').val(),
            grid_id = is_manage || is_manage_blocks ? 0 : _hover_element.prop('id').replace('grid_', ''),
            href = 'block_manager.update_block?block_data[type]=' + block_type;

        if (is_manage_blocks) {
          href += '&ajax_update=1';
          href += '&html_id=' + html_id;
          href += '&assign_to=' + result_id;
        } else {
          href += '&snapping_data[grid_id]=' + grid_id;
          href += '&selected_location=' + (typeof selected_location == 'undefined' ? 0 : selected_location);
        }

        var prop_container = 'new_block_' + block_type + '_' + grid_id;

        if ($('#' + prop_container).length == 0) {
          // Create properties container
          var container = $('<div id="' + prop_container + '"></div>').appendTo('body');
        }

        $('#' + prop_container).ceDialog('open', {
          href: fn_url(href),
          title: Tygh.tr('add_block') + ': ' + $(this).find('strong').text(),
          destroyOnClose: true
        });
      } else if (action === 'existing-block') {
        var is_manage = $(this).hasClass('bm-manage'),
            block_id = $(this).find('input[name="block_id"]').val(),
            block_type = $(this).find('input[name="type"]').val(),
            grid_id = $(this).find('input[name="grid_id"]').val(),
            block_title = $(this).find('.select-block-description > strong').text();

        if (is_manage || is_manage_blocks) {
          var prop_container = 'new_block_' + block_type + '_block_' + block_id;

          if ($('#' + prop_container).length == 0) {
            // Create properties container
            var container = $('<div id="' + prop_container + '"></div>').appendTo('body');
          }

          if (is_manage_blocks) {
            var data = {
              block_data: {
                block_id: block_id,
                type: block_type
              },
              assign_to: result_id
            };

            _self.sendRequest('', 'update_block', data, is_manage_blocks);
          } else {
            var href = 'block_manager.update_block?';
            href += 'block_data[type]=' + block_type;
            href += '&block_data[block_id]=' + block_id;
            href += '&selected_location=' + (typeof selected_location == 'undefined' ? 0 : selected_location);
            $('#' + prop_container).ceDialog('open', {
              href: fn_url(href),
              title: $(this).find('strong').text()
            });
          }
        } else {
          var elm = $('<div class="block base-block" data-block-id="' + block_id + '" id="block_' + block_id + '" title="' + block_title + '">' + $('.base-block').html() + '</div>');
          $('.block-header-title', elm).text(block_title);
          $('[data-ca-block-manager="block_id"]', elm).text('#' + block_id);
          $('.block-header-icon', elm).addClass('bmicon-' + block_type.replace('_', '-'));
          $('.block-header', elm).prop('title', block_title);
          elm.data('caBlockName', block_title); // Check if the same block already exists in the grid

          var blocks = _hover_element.find('.' + _init_params.block_class);

          var exists = false;
          blocks.each(function () {
            if ($(this).data('block-id') == block_id) {
              exists = true;
              return false;
            }
          });

          if (exists) {
            $.ceNotification('show', {
              type: 'E',
              title: _.tr('error'),
              message: _.tr('block_already_exists_in_grid')
            });
            return false;
          }

          if (_hover_element.find('.' + _init_params.block_class + ':last').length) {
            elm.insertAfter(_hover_element.find('>.' + _init_params.block_class + ':last'));
          } else {
            elm.prependTo(_hover_element);
          }

          _snapBlocks(elm);

          _self.buildMenu(elm);

          _self.checkMenuItems(elm.parent());

          var dlg = $.ceDialog('get_last');
          dlg.ceDialog('close');
          var is_single_for_location = $(this).hasClass('bm-block-single-for-location');

          if (is_single_for_location) {
            elm.find('.bm-action-delete').addClass('bm-block-single-for-location'); // mark remove button

            dlg.dialog('destroy').remove();
          }
        }
      }

      if (is_manage_blocks) {
        $.ceDialog('get_last').ceDialog('close');
      }
    });
  };

  this.hideControls = function () {
    // hide menus
    this.toggleMenus(false); // disable drag'n'drop

    this.toggleDragNDrop(false);
  };

  this.toggleMenus = function (flag) {
    if (flag) {
      $(_init_params.controls_selector).each(function (i, menu) {
        var $menu = $(menu);

        if (!$menu.hasClass('keep-hidden')) {
          $menu.removeClass('hidden');
        }
      });
    } else {
      $(_init_params.controls_selector).addClass('hidden');
    }
  };

  this.toggleDragNDrop = function (flag) {
    $(_init_params.sortable_selector).sortable(flag ? 'enable' : 'disable');
  };

  this.initDeviceAvailabilitySwitcher = function (params) {
    var that = this;
    $(params.switch_selector).each(function (i, elm) {
      var $switcher = $(elm);
      $switcher.on('click', function (e) {
        var device = $(this).attr(params.device_attribute); // toggle blocks

        $(params.block_selector + '[' + params.block_availability_prefix + device + '="true"]').removeClass('hidden');
        $(params.block_selector + '[' + params.block_availability_prefix + device + '="false"]').addClass('hidden'); // toggle active class

        $(params.switch_selector).removeClass(params.switcher_active_class);
        $(this).addClass(params.switcher_active_class); // show filter reset

        $(params.reset_selector).removeClass(params.switcher_active_class); // save filter

        $.cookie.set(params.storage_cookie, device);
        that.hideControls();
      });
    });
    $(params.reset_selector).on('click', function (e) {
      // reset active class
      $(params.block_selector).removeClass('hidden');
      $(params.switch_selector).removeClass(params.switcher_active_class);
      $(this).addClass(params.switcher_active_class); // reset saved filter

      $.cookie.set(params.storage_cookie, null); // show menus

      that.toggleMenus(true); // enable drag'n'drop

      that.toggleDragNDrop(true);
    });
    var device = $.cookie.get(params.storage_cookie);

    if (device) {
      $(params.switch_selector + '[' + params.device_attribute + '="' + device + '"]').trigger('click');
    }
  };

  this.recalculateClearLines = function (parent) {
    // Re-create "clearfix" divs to make correct grid lines
    var clears_data = {
      containers: {},
      grids: {}
    }; // Remove all "clear" div's

    $('.' + _init_params.container_class + ' div.clearfix', parent || _init_params.parent).remove(); // We need only first element of each grid blocks

    $('.' + _init_params.grid_class + ':first-child', parent || _init_params.parent).each(function () {
      var jelm = $(this);

      var parent_type = _determineElementType(jelm.parent());

      if (parent_type.type == 'container') {
        var max_width = parseInt(jelm.parent().prop('class').match(/container_([0-9]+)/i)[1]);
      } else {
        var max_width = parseInt(jelm.parent().prop('class').match(/grid_([0-9]+)/i)[1]);
      }

      var current_width = 0;
      var last_grid = {};
      jelm.parent().find('>.' + _init_params.grid_class).each(function () {
        var grid = $(this);
        var grid_width = parseInt(grid.prop('class').match(/grid_([0-9]+)/i)[1]);
        var grid_prefix = grid.prop('class').match(/prefix_([0-9]+)/i);
        var grid_suffix = grid.prop('class').match(/suffix_([0-9]+)/i);
        grid_prefix = grid_prefix == null ? 0 : parseInt(grid_prefix[1]);
        grid_suffix = grid_suffix == null ? 0 : parseInt(grid_suffix[1]);
        grid_width += grid_prefix + grid_suffix;

        if (current_width + grid_width > max_width) {
          if (grid.prev().length > 0) {
            var clear_id = grid.prev().prop('id').replace('grid_', '');

            if (clear_id != '') {
              clears_data.grids[clear_id] = true;
            }

            $('<div class="clearfix"></div>').insertBefore(grid);
          }

          current_width = grid_width;
        } else {
          current_width += grid_width;
        }

        last_grid = grid;
      });

      if (last_grid.length > 0) {
        var clear_id = last_grid.prop('id').replace('grid_', '');

        if (typeof clears_data.grids[clear_id] == 'undefined') {
          clears_data.grids[clear_id] = true;
          $('<div class="clearfix"></div>').insertAfter(last_grid);
        }
      }
    });
    $('.' + _init_params.container_class, _init_params.parent).each(function () {
      var container_id = $(this).prop('id').replace('container_', '');
      clears_data.containers[container_id] = true;
    });
    return clears_data;
  };

  this.sendRequest = function (mode, action, data, is_manage_blocks) {
    if (!data.return_url) {
      data.return_url = _.current_url;
    }

    if (mode == 'grid') {
      data.clears_data = _self.recalculateClearLines();
    }

    var controller = typeof data['controller'] == 'undefined' ? 'block_manager' : data['controller'],
        url = controller + (mode ? '.' + mode : '') + (action ? '.' + action : '');
    $.ceAjax('request', fn_url(url), {
      data: data,
      callback: is_manage_blocks ? false : _parseResponse,
      method: 'post'
    });
  };

  this.calculateLevels = function () {
    // Re-init sortable zones
    $(_init_params.containers + ',.cm-sortable-container').each(function () {
      if ($(this).hasClass('ui-sortable')) {
        //is inited
        $(this).sortable('refresh');
      }
    });
    $('.' + _init_params.grid_class, _init_params.parent).each(function () {
      var jelm = $(this);

      var level = _self.getLevel($(this));

      jelm.prop('class', jelm.prop('class').replace(/level-[0-9]+/, ''));
      jelm.addClass('level-' + level);

      if (jelm.find('.' + _init_params.grid_class).length == 0) {
        jelm.addClass('cm-sortable-grid');
      } else {
        jelm.removeClass('cm-sortable-grid');
      }
    });
    $('.' + _init_params.container_class + ',.' + _init_params.grid_class, _init_params.parent).each(function () {
      var jelm = $(this);

      _self.calculateAlphaOmega(jelm);
    }); // Re-init droppable zone

    $('.cm-sortable-grid').sortable(_init_params);

    if (_init_params.grid_items !== null) {
      $('.' + _init_params.container_class + ',' + _init_params.grid_items + ':not(.cm-sortable-grid)').sortable({
        items: '>' + _init_params.grid_items,
        handle: ".bm-control-menu",
        tolerance: 'pointer',
        update: function update(event, ui) {
          var grid = $(ui.item);
          var parent_container = $(ui.item).parent();
          var grid_id = grid.prop('id').replace('grid_', '');

          _self.calculateAlphaOmega(parent_container);

          var grids_snapping = BlockManager.snapGrid({
            grid_id: grid_id
          });

          _self.sendRequest('grid', 'update', grids_snapping);
        },
        start: function start(event, ui) {
          var grid = $(ui.item);

          if (grid.hasClass('alpha')) {
            grid.data('alpha', true);
          }

          if (grid.hasClass('omega')) {
            grid.data('omega', true);
          }

          grid.removeClass('alpha').removeClass('omega');
          $('div.clearfix', grid.parent()).remove();
        },
        stop: function stop(event, ui) {
          var grid = $(ui.item);

          if (grid.data('alpha')) {
            grid.addClass('alpha');
            grid.data('alpha', false);
          }

          if (grid.data('omega')) {
            grid.addClass('omega');
            grid.data('omega', false);
          }

          var parent_container = grid.parent();

          _self.calculateAlphaOmega(parent_container);
        },
        change: function change(event, ui) {
          var grid = $(ui.item);
          var parent_container = grid.parent();

          _self.calculateAlphaOmega(parent_container);
        }
      });
    }
  };

  this.setBlockManagerActions = function (elm, response) {
    if (response.mode === 'snapping') {
      response.bm_actions = response.bm_actions || {};

      for (var action in response.bm_actions) {
        var control_class = '.bm-action-' + action;

        if (!response.bm_actions[action]) {
          $(control_class, elm).remove();
        }
      }

      return true;
    }

    return false;
  };

  this.getLevel = function (elm) {
    var level = 1;

    while (!elm.parent().hasClass(_init_params.container_class)) {
      elm = elm.parent();
      level++;
    }

    return level;
  };

  this.calculateAlphaOmega = function (element) {
    var items = element.children('.' + _init_params.grid_class);

    if (element.hasClass(_init_params.container_class)) {
      var width = element.prop('class').match(/container_([0-9]+)/i)[1];
    } else {
      var width = element.prop('class').match(/grid_([0-9]+)/i)[1];
    }

    var line_width = 0;
    var index = 1;
    var alpha = false;
    var omega = false;
    var prev_elm = null;
    items.each(function () {
      var jelm = $(this);

      if (jelm.hasClass('ui-sortable-helper')) {
        return jelm;
      }

      var elm_width = parseInt(jelm.prop('class').match(/grid_([0-9]+)/i)[1]);
      var elm_prefix = jelm.prop('class').match(/prefix_([0-9]+)/i);
      var elm_suffix = jelm.prop('class').match(/suffix_([0-9]+)/i);
      elm_prefix = elm_prefix == null ? 0 : parseInt(elm_prefix[1]);
      elm_suffix = elm_suffix == null ? 0 : parseInt(elm_suffix[1]);
      elm_width += elm_prefix + elm_suffix;
      jelm.removeClass('alpha').removeClass('omega');

      if (alpha === false) {
        jelm.addClass('alpha');
        alpha = true;
      }

      if (line_width + elm_width == width) {
        jelm.addClass('omega');
        alpha = false;
        line_width = 0;
      } else if (line_width + elm_width > width) {
        jelm.addClass('alpha');

        if (prev_elm != null) {
          prev_elm.addClass('omega');
        }

        if (elm_width != width) {
          alpha = true;
        } else {
          alpha = false;
        }

        line_width = elm_width;
      } else {
        line_width += elm_width;
      }

      if (index == items.length) {
        jelm.addClass('omega');
      }

      index++;
      prev_elm = jelm;
    });
  };

  this.recheckBlockStatuses = function (elm) {
    if (elm.hasClass('grid-off') || elm.hasClass('container-off')) {
      elm.find('.' + _init_params.block_class).addClass('block-off');
    } else {
      elm.find('.' + _init_params.block_class).each(function () {
        if ($(this).data('caStatus') == 'active') {
          $(this).removeClass('block-off');
        }
      });
    }
  };

  this.getPropertyValue = function (property, elm) {
    var value = '';
    elm = elm || _hover_element;

    if (property == 'columns') {
      value = elm.prop('class').match(/container_/) ? parseInt(elm.prop('class').match(/container_([0-9]+)/i)[1]) : 0;
    } else if (property == 'width') {
      value = elm.prop('class').match(/grid_/) ? parseInt(elm.prop('class').match(/grid_([0-9]+)/i)[1]) : 0;
    } else if (property == 'alpha') {
      value = elm.prop('class').match(/alpha/i) ? '1' : '0';
    } else if (property == 'omega') {
      value = elm.prop('class').match(/omega/i) ? '1' : '0';
    } else if (property == 'prefix') {
      value = elm.prop('class').match(/prefix_/) ? parseInt(elm.prop('class').match(/prefix_([0-9]+)/i)[1]) : 0;
    } else if (property == 'suffix') {
      value = elm.prop('class').match(/suffix_/) ? parseInt(elm.prop('class').match(/suffix_([0-9]+)/i)[1]) : 0;
    }

    return value;
  };

  this.saveProperties = function (type, data) {
    switch (type) {
      case 'grid':
        if (!parseInt(data['grid_id'])) {
          elm = $('<div class="grid" id="new_element">' + $('.base-grid').html() + '</div>');

          if (_hover_element.find('.' + _init_params.grid_class + ':last').length) {
            elm.insertAfter(_hover_element.find('>.' + _init_params.grid_class + ':last'));
          } else {
            elm.prependTo(_hover_element);
          }
        } else {
          elm = _hover_element;
        }

        for (var key in data) {
          var value = data[key];

          if (key == 'width') {
            var elm_class = elm.prop('class').replace(/grid_[0-9]+/, ''); //Get element class without "grid_N" class

            elm_class += ' grid_' + value;
            elm.prop('class', elm_class);
          } else if (key == 'offset') {
            var elm_class = elm.prop('class').replace(/prefix_[0-9]+/, ''); //Get element class without "prefix_N" class

            if (value > 0) {
              elm_class += ' prefix_' + value;
            }

            elm.prop('class', elm_class);
          } else if (key == 'suffix') {
            var elm_class = elm.prop('class').replace(/suffix_[0-9]+/, ''); //Get element class without "suffix_N" class

            if (value > 0) {
              elm_class += ' suffix_' + value;
            }

            elm.prop('class', elm_class);
          } else if (key == 'content_align') {
            elm.removeClass('bm-left-align bm-right-align bm-full-width');

            if (value == 'LEFT') {
              elm.addClass('bm-left-align');
            } else if (value == 'RIGHT') {
              elm.addClass('bm-right-align');
            } else {
              elm.addClass('bm-full-width');
            }
          }
        } // Rebuild menu for new element according to the new settings


        _self.buildMenu(elm);

        _self.checkMenuItems(elm.parent());

        break;

      case 'container':
        for (var key in data) {
          var value = data[key];

          if (key == 'container_data[width]') {
            var elm_class = _hover_element.prop('class').replace(/container_[0-9]+/, ''); //Get element class without "container_N" class


            elm_class += ' container_' + value;

            _hover_element.prop('class', elm_class);
          }
        }

        break;

      default:
        break;
    }

    _self.calculateLevels();

    _self.buildMenu(_hover_element);

    $('.' + _init_params.block_class, _hover_element).each(function () {
      _self.buildMenu($(this));
    });
    return data;
  };

  this.deleteStructure = function (element) {
    element = element || _hover_element;

    var elm_data = _determineElementType(element);

    if (elm_data.type == 'grid') {
      var snappings = {};
      var grids = $(element).parent().find('.' + _init_params.grid_class);
      grids.each(function () {
        jelm = $(this);
        var grid_id = jelm.prop('id').replace('grid_', '');
        var action = grid_id == element.prop('id').replace('grid_', '') ? 'delete' : 'update';
        snappings[grid_id] = {
          action: action,
          grid_data: {
            grid_id: grid_id
          }
        };
      }); // Delete grid and recalculate levels and alpha/omega parameters

      var parent_grid = element.parent();
      element.remove();

      _self.calculateLevels();

      _self.checkMenuItems(parent_grid);

      for (var i in snappings) {
        if (snappings[i].action == 'delete') {
          $('#grid_' + snappings[i].grid_data.grid_id).remove();
        } else {
          jelm = $('#grid_' + snappings[i].grid_data.grid_id);

          if (jelm.length > 0) {
            // We can remove parent grid with other grids inside
            snappings[i].grid_data.alpha = _self.getPropertyValue('alpha', jelm);
            snappings[i].grid_data.omega = _self.getPropertyValue('omega', jelm);
          }
        }
      }

      return snappings;
    } else if (elm_data.type == 'block') {
      var snappings = {
        0: {
          action: 'delete',
          snapping_id: element.prop('id').replace('snapping_', '')
        }
      };
      var parent_grid = $('#snapping_' + snappings[0].snapping_id).parent();
      $('#snapping_' + snappings[0].snapping_id).remove();

      _self.checkMenuItems(parent_grid);

      return snappings;
    }

    return false;
  };

  this.getMaxWidth = function (elm, is_new) {
    var width = 0;
    elm = elm || _hover_element;
    is_new = is_new || false;

    if (elm.hasClass(_init_params.block_class)) {
      elm = elm.parent();
    }

    if (elm.hasClass(_init_params.container_class)) {
      width = parseInt(elm.prop('class').match(/container_([0-9]+)/i)[1]);
    } else if (elm.hasClass(_init_params.grid_class)) {
      if (is_new) {
        var elmClass = elm.prop('class'),
            offset = /prefix_([0-9])/i.exec(elmClass) ? parseInt(/prefix_([0-9])/i.exec(elmClass)[1]) : 0;
        width = parseInt(elmClass.match(/grid_([0-9]+)/i)[1]) - offset;
      } else {
        var elmParentClass = elm.parent().prop('class'),
            _offset = /prefix_([0-9])/i.exec(elmParentClass) ? parseInt(/prefix_([0-9])/i.exec(elmParentClass)[1]) : 0;

        if (elm.parent().hasClass('container')) {
          width = parseInt(elmParentClass.match(/container_([0-9]+)/i)[1]) - _offset;
        } else {
          width = parseInt(elmParentClass.match(/grid_([0-9]+)/i)[1]) - _offset;
        }
      }
    }

    return width;
  };

  this.getMinWidth = function (elm) {
    elm = elm || _hover_element;

    var widthChild = _self.getMaxWidthChildren(elm),
        minWidth = widthChild > 0 ? widthChild : 1;

    return minWidth;
  };

  this.getMaxOffset = function (elm, width) {
    elm = elm || _hover_element;

    var maxWidthChildren = _self.getMaxWidthChildren(elm),
        minWidth = maxWidthChildren > 0 ? maxWidthChildren : 1,
        widthElm = width ? width : _self.getPropertyValue('width', elm),
        maxOffset = widthElm - minWidth + 1;

    return maxOffset;
  };

  this.getMaxWidthChildren = function (elm) {
    elm = elm || _hover_element;
    var maxWidth = 0,
        children = elm.children('.' + _init_params.grid_class);

    if (!children.length) {
      return maxWidth;
    }

    var childrenWidth = children.map(function () {
      return _self.getPropertyValue('width', $(this));
    }).get();
    return Math.max.apply(Math, _toConsumableArray(childrenWidth));
  };

  this.snapGrid = function (grid) {
    if (parseInt(grid.grid_id)) {
      var selector = '#grid_' + grid.grid_id;
    } else {
      var selector = '#new_element';
    }

    var snapping = {};
    var grids = $(selector).parent().find('>.' + _init_params.grid_class);
    grids.each(function () {
      var _grid = $(this);

      var id = _grid.index();

      snapping[id] = {};
      snapping[id].grid_data = {};

      if (_grid.prop('id') == 'new_element') {
        snapping[id].action = 'add';

        for (var i in grid) {
          snapping[id].grid_data[i] = grid[i];
        }
      } else {
        if (grid['grid_id'] == _grid.prop('id').replace('grid_', '')) {
          // Move data from form to updating snapping data
          snapping[id].grid_data = grid;
        }

        snapping[id].action = 'update';
        snapping[id].grid_data.grid_id = _grid.prop('id').replace('grid_', '');
      }

      snapping[id].grid_data.alpha = _self.getPropertyValue('alpha', _grid);
      snapping[id].grid_data.omega = _self.getPropertyValue('omega', _grid);
      snapping[id].grid_data.order = id;
    });
    return {
      snappings: snapping
    };
  };

  this.buildMenu = function (element) {
    // You must control this functionality when changing anything in control menu
    // Rebuild menu if width of element doesn't allow to use "full width" menu
    var type = _determineElementType(element).type;

    var width = 0;

    if (type == 'grid') {
      width = _self.getPropertyValue('width', element); // Change header title from "GRID X" to "GRID Y"

      var title = $('> .grid-control-menu > .grid-control-title', element).html();
      title = title.replace(/[0-9]+/, width);
      $('> .grid-control-menu > .grid-control-title', element).html(title);
    } else if (type == 'block') {
      width = _self.getPropertyValue('width', element.parent());
    }

    if (width >= 1 && width <= 2) {
      $('> .bm-full-menu', element).hide();
      $('> .bm-compact-menu', element).show();
      $('> .grid-control-menu > .grid-control-title', element).hide();
    } else if (width > 0) {
      $('> .bm-full-menu', element).show();
      $('> .bm-compact-menu', element).hide();
      $('> .grid-control-menu > .grid-control-title', element).show();
    }

    return true;
  };

  this.checkMenuItems = function (elements) {
    elements.each(function () {
      var jelm = $(this);
      var has_blocks = $('> .' + _init_params.block_class, jelm).length > 0 ? true : false;
      var has_grids = $('> .' + _init_params.grid_class, jelm).length > 0 ? true : false;
      $('> .bm-control-menu .bm-action-add-block, > .bm-control-menu .bm-action-add-grid', jelm).show();

      if (has_blocks) {
        $('> .bm-control-menu .bm-action-add-grid', jelm).hide();
      }

      if (has_grids) {
        $('> .bm-control-menu .bm-action-add-block', jelm).hide();
      }
    });
  };

  this.openObjectPropertiesForm = function (objectId, objectType) {
    var $objectMenu = $('#' + objectType + '_' + objectId + ' > .bm-control-menu:first');

    if ($objectMenu.length) {
      $('.bm-action-properties', $objectMenu).trigger('click');
    }
  };
}

(function (_, $) {
  $.ceEvent('on', 'ce.formpost_grid_update_form', function (frm, c_elm) {
    var form_data = frm.serializeObject();
    form_data = BlockManager.saveProperties('grid', form_data);
    var grids_snapping = BlockManager.snapGrid(form_data);
    BlockManager.sendRequest('grid', 'update', grids_snapping);
    return false;
  });
  $.ceEvent('on', 'ce.formpost_container_update_form', function (frm, c_elm) {
    var form_data = frm.serializeObject();
    form_data = BlockManager.saveProperties('container', form_data);
    BlockManager.sendRequest('container', 'update', form_data);
    return false;
  });
})(Tygh, Tygh.$);