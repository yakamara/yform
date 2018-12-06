
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

String.prototype.sha1 = function () {

  var msg = this;

  /**
   * Secure Hash Algorithm (SHA1)
   * http://www.webtoolkit.info/
   **/
  function rotate_left(n,s) {
    var t4 = ( n<<s ) | (n>>>(32-s));
    return t4;
  };
  function lsb_hex(val) {
    var str='';
    var i;
    var vh;
    var vl;
    for( i=0; i<=6; i+=2 ) {
      vh = (val>>>(i*4+4))&0x0f;
      vl = (val>>>(i*4))&0x0f;
      str += vh.toString(16) + vl.toString(16);
    }
    return str;
  };
  function cvt_hex(val) {
    var str='';
    var i;
    var v;
    for( i=7; i>=0; i-- ) {
      v = (val>>>(i*4))&0x0f;
      str += v.toString(16);
    }
    return str;
  };
  function Utf8Encode(string) {
    string = string.replace(/\r\n/g,'\n');
    var utftext = '';
    for (var n = 0; n < string.length; n++) {
      var c = string.charCodeAt(n);
      if (c < 128) {
        utftext += String.fromCharCode(c);
      }
      else if((c > 127) && (c < 2048)) {
        utftext += String.fromCharCode((c >> 6) | 192);
        utftext += String.fromCharCode((c & 63) | 128);
      }
      else {
        utftext += String.fromCharCode((c >> 12) | 224);
        utftext += String.fromCharCode(((c >> 6) & 63) | 128);
        utftext += String.fromCharCode((c & 63) | 128);
      }
    }
    return utftext;
  };
  var blockstart;
  var i, j;
  var W = new Array(80);
  var H0 = 0x67452301;
  var H1 = 0xEFCDAB89;
  var H2 = 0x98BADCFE;
  var H3 = 0x10325476;
  var H4 = 0xC3D2E1F0;
  var A, B, C, D, E;
  var temp;
  msg = Utf8Encode(msg);
  var msg_len = msg.length;
  var word_array = new Array();
  for( i=0; i<msg_len-3; i+=4 ) {
    j = msg.charCodeAt(i)<<24 | msg.charCodeAt(i+1)<<16 |
      msg.charCodeAt(i+2)<<8 | msg.charCodeAt(i+3);
    word_array.push( j );
  }
  switch( msg_len % 4 ) {
    case 0:
      i = 0x080000000;
      break;
    case 1:
      i = msg.charCodeAt(msg_len-1)<<24 | 0x0800000;
      break;
    case 2:
      i = msg.charCodeAt(msg_len-2)<<24 | msg.charCodeAt(msg_len-1)<<16 | 0x08000;
      break;
    case 3:
      i = msg.charCodeAt(msg_len-3)<<24 | msg.charCodeAt(msg_len-2)<<16 | msg.charCodeAt(msg_len-1)<<8 | 0x80;
      break;
  }
  word_array.push( i );
  while( (word_array.length % 16) != 14 ) word_array.push( 0 );
  word_array.push( msg_len>>>29 );
  word_array.push( (msg_len<<3)&0x0ffffffff );
  for ( blockstart=0; blockstart<word_array.length; blockstart+=16 ) {
    for( i=0; i<16; i++ ) W[i] = word_array[blockstart+i];
    for( i=16; i<=79; i++ ) W[i] = rotate_left(W[i-3] ^ W[i-8] ^ W[i-14] ^ W[i-16], 1);
    A = H0;
    B = H1;
    C = H2;
    D = H3;
    E = H4;
    for( i= 0; i<=19; i++ ) {
      temp = (rotate_left(A,5) + ((B&C) | (~B&D)) + E + W[i] + 0x5A827999) & 0x0ffffffff;
      E = D;
      D = C;
      C = rotate_left(B,30);
      B = A;
      A = temp;
    }
    for( i=20; i<=39; i++ ) {
      temp = (rotate_left(A,5) + (B ^ C ^ D) + E + W[i] + 0x6ED9EBA1) & 0x0ffffffff;
      E = D;
      D = C;
      C = rotate_left(B,30);
      B = A;
      A = temp;
    }
    for( i=40; i<=59; i++ ) {
      temp = (rotate_left(A,5) + ((B&C) | (B&D) | (C&D)) + E + W[i] + 0x8F1BBCDC) & 0x0ffffffff;
      E = D;
      D = C;
      C = rotate_left(B,30);
      B = A;
      A = temp;
    }
    for( i=60; i<=79; i++ ) {
      temp = (rotate_left(A,5) + (B ^ C ^ D) + E + W[i] + 0xCA62C1D6) & 0x0ffffffff;
      E = D;
      D = C;
      C = rotate_left(B,30);
      B = A;
      A = temp;
    }
    H0 = (H0 + A) & 0x0ffffffff;
    H1 = (H1 + B) & 0x0ffffffff;
    H2 = (H2 + C) & 0x0ffffffff;
    H3 = (H3 + D) & 0x0ffffffff;
    H4 = (H4 + E) & 0x0ffffffff;
  }
  var temp = cvt_hex(H0) + cvt_hex(H1) + cvt_hex(H2) + cvt_hex(H3) + cvt_hex(H4);

  return temp.toLowerCase();
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

});

