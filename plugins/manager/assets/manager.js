
function yform_manager_openDatalist(id, field, link, multiple)
{
  newLinkMapWindow(link+'&rex_yform_manager_opener[id]='+id+'&rex_yform_manager_opener[field]='+field+'&rex_yform_manager_opener[multiple]='+multiple);
}

function yform_manager_deleteDatalist(id, multiple){
  if(multiple == 1) {
    deleteREX(id, 'yform_MANAGER_DATALIST_', 'yform_MANAGER_DATALIST_SELECT_');
  } else {
    var a = new getObj("yform_MANAGER_DATANAME_"+id);
    a.obj.value = "";
    var a = new getObj("yform_MANAGER_DATA_"+id);
    a.obj.value = "";
  }
}

function yform_manager_moveDatalist(id, direction){
  moveREX(id, 'yform_MANAGER_DATALIST_', 'yform_MANAGER_DATALIST_SELECT_', direction);
}

function yform_manager_writeDatalist(id){
  writeREX(id, 'yform_MANAGER_DATALIST_', 'yform_MANAGER_DATALIST_SELECT_');
}

rex_retain_popup_event_handlers("rex:YForm_selectData");

function yform_manager_setData(id, data_id, data_name, multiple){

  var event = opener.jQuery.Event("rex:YForm_selectData");
  opener.jQuery(window).trigger(event, [data_id, data_name, multiple]);
  if (event.isDefaultPrevented()) {
    self.close();
  }

  if(multiple == 1) {
    var datalist = "yform_MANAGER_DATALIST_SELECT_"+id;
    var source = opener.document.getElementById(datalist);
    var sourcelength = source.options.length;

    option = opener.document.createElement("OPTION");
    option.text = data_name;
    option.value = data_id;

    source.options.add(option, sourcelength);
    opener.writeREX(id, 'yform_MANAGER_DATALIST_', 'yform_MANAGER_DATALIST_SELECT_');
  }else {
    var data_field_name = "yform_MANAGER_DATANAME_"+id;
    var data_field_id = "yform_MANAGER_DATA_"+id;
    opener.document.getElementById(data_field_name).value = data_name;
    opener.document.getElementById(data_field_id).value = data_id;
    self.close();
  }

}

$(document).on('rex:ready', function (event, container) {
  container.find('form.yform-manager-multi-edit').each(function () {
    var $form = $(this);
    $form.find('[data-multi-edit-checkbox]').each(function () {
      var $checkbox = $(this);
      var $wrapper = $checkbox.closest('div');
      var id = $wrapper.attr('id');
      // extract "_multi_edit"
      id = id.substring(0, id.length - 11);
      var $target = $form.find('#' + id);

      var toggle = function () {
        if ($checkbox.prop('checked')) {
          $target.find(':input').prop('disabled', false);
          $target.find(':input[data-multi-edit-original-disabled]').prop('disabled', true).removeAttr('data-multi-edit-original-disabled');
          $target.find('a.btn').removeClass('disabled');
          $target.find('a.btn[data-multi-edit-original-disabled]').addClass('disabled').removeAttr('data-multi-edit-original-disabled');
        } else {
          $target.find(':input:disabled').attr('data-multi-edit-original-disabled', 1);
          $target.find(':input').prop('disabled', true);
          $target.find('a.btn[data-disabled], a.btn.disabled').attr('data-multi-edit-original-disabled', 1);
          $target.find('a.btn').addClass('disabled');
        }
      };

      toggle();
      $checkbox.change(toggle);
    });
  });

  container.find('#rex-yform-history-modal').on('hidden.bs.modal', function () {
    $(this).removeData('bs.modal').find(".modal-content").empty();
  });

});

var be_relation_media_counter = 100; // no conflicts to 100 media in one page
var be_relation_counter = 100; // no conflicts to 100 media in one page

$(document).on('rex:ready', function (event, container) {

  container.find('[data-yform-be-relation-moveup]').each(function() {
    $(this).on('click', function() {
      var $thisElement = $(this).closest('[data-yform-be-relation-item]');
      var $prev = $thisElement.prev();
      if($prev) {
        $prev.before($thisElement);
      }
    });
  });

  container.find('[data-yform-be-relation-movedown]').each(function() {
    $(this).on('click', function() {
      var $thisElement = $(this).closest('[data-yform-be-relation-item]');
      var $next = $thisElement.next();
      if($next) {
        $next.after($thisElement);
      }
    });
  });

  container.find('[data-yform-be-relation-delete]').each(function() {
    $(this).on('click', function() {
      $('#'+$(this).attr('data-yform-be-relation-delete')).remove();
    })
  });

  container.find('[data-yform-be-relation-sortable]').each(function() {
    $(this).sortable({
      handle: ".sorthandle"
    });
  });

  container.find('[data-yform-be-relation-add]').each(function() {
    $(this).on('click', function() {

      var $wrapper = $(this).closest('[data-yform-be-relation-form]');
      var id = $wrapper.attr('id');
      var relationKey = $wrapper.attr('data-yform-be-relation-key');

      var prototypeForm = $wrapper.attr('data-yform-be-relation-form');
      var index = $wrapper.attr('data-yform-be-relation-index');

      ++index;
      $wrapper.attr('data-yform-be-relation-index',index);

      var rep = new RegExp(relationKey, "g");

      prototypeForm = prototypeForm.replace(rep, index);
      var $prototypeForm = $(prototypeForm);

      var dataItems = $(this).attr('data-yform-be-relation-add-position');

      var $dataItems;

      if (typeof dataItems !== "undefined") {
        $dataItems = $wrapper.find('[data-yform-be-relation-item='+dataItems+']'); // id matchen
        $dataItems.after($prototypeForm);

      } else {
        $dataItems = $wrapper.find('[data-yform-be-relation-item='+id+']'); // id matchen
        $dataItems.append($prototypeForm);

      }

      $prototypeForm.trigger('rex:ready', [$prototypeForm]);

    });

  });

  container.find('[data-be-relation-wrapper]').each(function() {

    be_relation_counter++;

    var regexpDataset = [
      new RegExp("(moveYFormDatasetList\\(?\\d+)", 'g'),
      new RegExp("(openYFormDatasetList\\(?\\d+)", 'g'),
      new RegExp("(deleteYFormDatasetList\\(?\\d+)", 'g'),
      new RegExp("(openYFormDataset\\(?\\d+)", 'g'),
      new RegExp("(deleteYFormDataset\\(?\\d+)", 'g')
    ];

    $(this)
        .find("[id^='YFORM_DATASETLIST_SELECT_'],[id^='YFORM_DATASETLIST_FIELD_'],[id^='YFORM_DATASET_SELECT_'],[id^='YFORM_DATASET_FIELD_']").each(function() {
      $(this).attr("id", $(this).attr("id") + be_relation_counter);
    });

    $(this)
        .find("[onclick]").each(function() {
      var elementOnClick = $(this).attr("onclick");
      for (var i in regexpDataset) {
        elementOnClick = elementOnClick.replace(regexpDataset[i], '$1' + be_relation_counter);
      }
      $(this).attr("onclick", elementOnClick);
    });

  });

  container.find('[data-be-media-wrapper]').each(function() {

    be_relation_media_counter++;

    $(this)
        .find("[id^='REX_MEDIA_'],[id^='REX_MEDIALIST_SELECT_']").each(function() {
      $(this).attr("id", $(this).attr("id") + be_relation_counter);
    });

    var regexpMedia = [
      new RegExp("(openREXMedia\\(?\\d+)", 'g'),
      new RegExp("(addREXMedia\\(?\\d+)", 'g'),
      new RegExp("(deleteREXMedia\\(?\\d+)", 'g'),
      new RegExp("(viewREXMedia\\(?\\d+)", 'g'),
      new RegExp("(moveREXMedialist\\(?\\d+)", 'g'),
      new RegExp("(openREXMedialist\\(?\\d+)", 'g'),
      new RegExp("(addREXMedialist\\(?\\d+)", 'g'),
      new RegExp("(deleteREXMedialist\\(?\\d+)", 'g'),
      new RegExp("(viewREXMedialist\\(?\\d+)", 'g'),
    ];

    $(this)
        .find("[onclick]").each(function() {
      var elementOnClick = $(this).attr("onclick");

      console.log(elementOnClick);
      for (var i in regexpMedia) {
        elementOnClick = elementOnClick.replace(regexpMedia[i], '$1' + be_relation_counter);
      }
      $(this).attr("onclick", elementOnClick);
      console.log(elementOnClick);

    });

    // replace be medialist
    $(this).find('select[id^="REX_MEDIALIST_"]').each(function() {
      var $input  = $(this).parent().children('input:first');
      var id = $(this).prop('id').replace('REX_MEDIALIST_SELECT_', '');
      $input.prop('id', 'REX_MEDIALIST_'+ id);
    });

  });

});

