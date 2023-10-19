(function (_, $) {
  // Init
  function initHelpCenter($helpCenter) {
    if ($helpCenter.data('caHelpCenterIsInited') && $helpCenter.data('caHelpCenterActiveDispatch') !== $helpCenter.data('caHelpCenterDefaultDispatch')) {
      switchDispatch($helpCenter, $helpCenter.data('caHelpCenterDefaultDispatch'));
      return;
    } else if ($helpCenter.data('caHelpCenterIsInited')) {
      return;
    }

    loadHelpData($helpCenter);
    initHelpEvent();
  }

  ; // Switch dispatch

  function switchDispatch($helpCenter, dispatch) {
    if (!$helpCenter.length) {
      return;
    } // If the dispatch is empty, then open the main dispatch


    dispatch = dispatch || ''; // Update request data

    var request = $helpCenter.data('caHelpCenterRequest');
    request.dispatch = dispatch;
    $helpCenter.data('caHelpCenterRequest', request);
    $helpCenter.data('caHelpCenterActiveDispatch', dispatch);
    loadHelpData($helpCenter);
  } // Reset dispatch


  function resetDispatch() {
    switchDispatch($(this).closest('[data-ca-help-center="main"]'));
  } // API


  function loadHelpData($helpCenter) {
    if (!$helpCenter.length) {
      return;
    }

    var helpCenterId = $helpCenter.attr('id');
    $.ceAjax('request', $helpCenter.data('caHelpCenterServerUrl') + '?' + new URLSearchParams($helpCenter.data('caHelpCenterRequest')).toString(), {
      caching: false,
      hidden: true,
      callback: renderHelpCenter,
      data: {
        helpCenterId: helpCenterId
      }
    });
  }

  ; // Main render help center

  function renderHelpCenter(data, params) {
    if (!data.chapters || !data.chapters.length) {
      return;
    }

    var $helpCenter = $('#' + params.data.helpCenterId);
    var elems = {
      $helpCenter: $helpCenter,
      $sectionsTarget: $('[data-ca-help-center="sectionsTarget"]', $helpCenter),
      $navsContentTarget: $('[data-ca-help-center="navsContentTarget"]', $helpCenter),
      $navs: $('[data-ca-help-center="navs"]', $helpCenter),
      $sectionsInjection: $('[data-ca-help-center="sectionsInjection"]', $helpCenter)
    };
    data = getAdditionalData(data, elems.$helpCenter);
    data = injectSections(data, elems.$sectionsInjection);
    var newElems = getHelpCenterHtml(data, elems.$helpCenter);

    if (data.new_blocks_count > 0) {
      $('.help-center-popup__icon').attr('data-ca-help-center-counter', data.new_blocks_count);
    }

    elems.$sectionsTarget.empty();
    elems.$navsContentTarget.empty();
    $(newElems.newSections).appendTo(elems.$sectionsTarget);
    $(newElems.newNavsContent).appendTo(elems.$navsContentTarget);
    elems.$navs.ceTabs();
    elems.$helpCenter.data('caHelpCenterIsInited', 1);
  } // Sections injection


  function injectSections(data, $sectionsInjection) {
    if (!data.chapters || !data.chapters.length) {
      return;
    }

    var sectionsInjection = $.parseJSON($sectionsInjection.html());
    data.new_blocks_count = 0;
    data.chapters.map(function (chaptersItem, chaptersIndex) {
      chaptersItem.sections.map(function (sectionsItem, sectionsIndex) {
        sectionsInjection.forEach(function (injectionSection) {
          if (sectionsItem.id !== injectionSection.id) {
            return;
          }

          data.chapters[chaptersIndex].sections[sectionsIndex] = injectionSection;
          data.chapters[chaptersIndex].sections[sectionsIndex].isDisabled = false;

          if (injectionSection.blocks.length && data.customer_last_update > data.timestamp_last_view) {
            data.new_blocks_count++;
          }
        });

        if (!sectionsItem.blocks || !sectionsItem.blocks.length) {
          return;
        }

        sectionsItem.blocks.map(function (block) {
          if (block.date_added && block.date_added > data.timestamp_last_view) {
            block.new = true;
            data.new_blocks_count++;
            sectionsItem.new = true;
          }
        });
      });
    });
    return data;
  } // Get help center rendered template (HTML)


  function getHelpCenterHtml(data, $helpCenter) {
    if (!data.chapters || !data.chapters.length) {
      return;
    }

    var newSections = [];
    var newNavsContent = [];
    var newNavsContentTemp = [];
    data.chapters.map(function (chaptersItem, chaptersIndex) {
      chaptersItem.sections.map(function (sectionsItem, sectionsIndex) {
        sectionsItem = getAdditionalDataForSectionItem(sectionsItem, sectionsIndex, chaptersItem, chaptersIndex, data);
        var $section = renderSection(renderBlocks(sectionsItem, $helpCenter), sectionsItem, $helpCenter);
        newNavsContentTemp.push(renderNavsItem(data, sectionsItem, chaptersItem, $helpCenter));

        if (!$section) {
          return;
        }

        newSections.push($section);
      });
      newNavsContent.push(renderNavsChapter(chaptersItem, newNavsContentTemp, $helpCenter)[0]);
      newNavsContentTemp = [];
    });
    return {
      newSections: newSections,
      newNavsContent: newNavsContent
    };
  } // Render blocks, and navigations


  function renderSection($blocks, sectionsItem, $helpCenter) {
    if (!sectionsItem || !$blocks || !$blocks.length) {
      return;
    }

    var $section = $(renderTemplate(sectionsItem, $('[data-ca-help-center="section"]', $helpCenter).html()));
    $blocks.appendTo($('[data-ca-help-center="articles"]', $section));
    return $section[0];
  }

  function renderBlocks(sectionsItem, $helpCenter) {
    if (!sectionsItem) {
      return;
    }

    var blocks = [];

    if (sectionsItem.blocks && sectionsItem.blocks.length) {
      sectionsItem.blocks.map(function (item) {
        item = getAdditionalDataForBlockItem(item, sectionsItem);
        return blocks.push(renderTemplate(item, $('[data-ca-help-center="block"]', $helpCenter).html()));
      });
    }

    return blocks.length ? $(blocks.join('')) : $(document.createTextNode(''));
  }

  function renderNavsItem(data, sectionsItem, chaptersItem, $helpCenter) {
    if (!sectionsItem) {
      return;
    }

    if (sectionsItem.blocks && sectionsItem.blocks.length && chaptersItem.id === data.relevant_chapter && $helpCenter.data('caHelpCenterActiveDispatch') !== '') {
      sectionsItem.blocks_counter_text = '(' + sectionsItem.blocks.length + ')';
    }

    return renderTemplate(sectionsItem, $('[data-ca-help-center="navItem"]', $helpCenter).html());
  }

  function renderNavsChapter(chaptersItem, navsContent, $helpCenter) {
    if (!chaptersItem) {
      return;
    }

    var $chapter = $(renderTemplate(chaptersItem, $('[data-ca-help-center="navChapter"]', $helpCenter).html()));
    $(navsContent.join('')).appendTo($('[data-ca-help-center="navSections"]', $chapter));
    return $chapter;
  } // Template engine


  function renderTemplate(data, template) {
    if (!data || !template) {
      return;
    }

    data = removeDangerousCode(data);
    var templater = new Function('data', "return `".concat(template, "`;"));
    return templater(data);
  } // Get additional info from data attributes


  function getAdditionalData(data, $helpCenter) {
    data.suffix = $helpCenter.data('caHelpCenterSuffix') || '';
    data.relevant_chapter = $helpCenter.data('caHelpCenterRelevantChapter') || '';
    data.no_data_relevant_text = $helpCenter.data('caHelpCenterNoDataRelevantText') || '';
    data.no_data_text = $helpCenter.data('caHelpCenterNoDataText') || '';
    data.product_release_info = $helpCenter.data('caHelpCenterProductReleaseInfo') || '';
    data.timestamp_last_view = $helpCenter.data('caHelpCenterTimestampLastView') || 0;
    data.customer_last_update = $helpCenter.data('caHelpCenterCustomerLastUpdate') || 0;
    return data;
  } // Get additional info for section


  function getAdditionalDataForSectionItem(sectionsItem, sectionsIndex, chaptersItem, chaptersIndex, data) {
    sectionsItem.suffix = data.suffix;
    sectionsItem.no_data_text = data.no_data_text;
    sectionsItem.product_release_info = data.product_release_info;
    sectionsItem.isShow = sectionsIndex === 0 && chaptersIndex === 0;
    sectionsItem.isDisabled = typeof sectionsItem.isDisabled === 'boolean' ? sectionsItem.isDisabled : !(sectionsItem.blocks && sectionsItem.blocks.length || sectionsItem.url);

    if (!sectionsItem.blocks || sectionsItem.blocks && !sectionsItem.blocks.length || typeof sectionsItem.columns === 'undefined') {
      sectionsItem.columns = 1;
    }

    if (chaptersItem.id === data.relevant_chapter) {
      sectionsItem.dispatch_name = data.dispatch_name;
      sectionsItem.no_data_text = data.no_data_relevant_text;
    }

    return sectionsItem;
  } // Get additional info for block item


  function getAdditionalDataForBlockItem(item, sectionsItem) {
    item.suffix = sectionsItem.suffix;
    item.section_id = sectionsItem.id;
    item.read_more = sectionsItem.read_more;
    item.new_tab = true;

    if (item.dispatch) {
      item.url = fn_url(item.dispatch);
      item.new_tab = false;
    }

    if (item.external_click_id) {
      item.url = '#';
      item.new_tab = false;
    }

    return item;
  } // Remove dangerous code from data. The exception is "html" and "description".


  function removeDangerousCode(data) {
    var safeData = {};

    for (var key in data) {
      safeData[key] = typeof data[key] === 'string' && key !== 'html' && key !== 'description' ? $($.parseHTML($.trim(data[key]))).text() : data[key];
    }

    return safeData;
  } // Help center mobile


  function openHelpCenterMobile() {
    // Close mobile menu
    $('.navbar-admin-top').toggleClass('open');
    $('body').toggleClass('noscrolling'); // Open dialog

    var _e = $('[data-ca-help-center="popupBtn"]');

    var params = $.ceDialog('get_params', _e);
    $('#' + _e.data('caTargetId')).ceDialog('open', params);
  } // Events


  function initHelpEvent() {
    $(_.doc).on('click', '[data-ca-help-center="resetDispatch"]', resetDispatch);
  } // Logging the last view help center


  function setLogLastView() {
    $.ceAjax('request', fn_url('help_center.set_timestamp_last_view'), {
      method: 'post',
      caching: false,
      hidden: true
    });
  }

  ;
  $(_.doc).on('click', '[data-ca-help-center="popupBtnMobile"]', openHelpCenterMobile);
  $.ceEvent('on', 'ce.commoninit', function ($context) {
    var $helpCenter = $('[data-ca-help-center="main"]', $context);

    if (!$helpCenter.length || !$context.is(document)) {
      return;
    }

    initHelpCenter($helpCenter);
  });
  $.ceEvent('on', 'ce.dialogshow', function ($context) {
    if ($context.data('caHelpCenter') !== 'popupContent') {
      return;
    }

    setLogLastView();
  });
  $.ceEvent('on', 'ce.dialogclose', function ($context) {
    if ($context.data('caHelpCenter') !== 'popupContent') {
      return;
    }

    initHelpCenter($('[data-ca-help-center="main"]', $context));
  });
})(Tygh, Tygh.$);