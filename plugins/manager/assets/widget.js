
function openYFormDataset(id, field, link)
{
    newLinkMapWindow(link+'&rex_yform_manager_opener[id]='+id+'&rex_yform_manager_opener[field]='+field+'&rex_yform_manager_opener[multiple]=0');
}
function openYFormDatasetList(id, field, link)
{
    newLinkMapWindow(link+'&rex_yform_manager_opener[id]='+id+'&rex_yform_manager_opener[field]='+field+'&rex_yform_manager_opener[multiple]=1');
}

function deleteYFormDataset(id){
    var a;
    a = new getObj('YFORM_DATASET_'+id);
    a.obj.value = '';
    a = new getObj('YFORM_DATASET_'+id+'_NAME');
    a.obj.value = '';
}

function deleteYFormDatasetList(id){
    deleteREX(id, 'YFORM_DATASETLIST_', 'YFORM_DATASETLIST_SELECT_');
}

function moveYFormDatasetList(id, direction){
    moveREX(id, 'YFORM_DATASETLIST_', 'YFORM_DATASETLIST_SELECT_', direction);
}

function writeYFormDatasetlist(id){
    writeREX(id, 'YFORM_DATASETLIST_', 'YFORM_DATASETLIST_SELECT_');
}

rex_retain_popup_event_handlers("rex:YForm_selectData");

function setYFormDataset(id, data_id, data_name, multiple){

    var eventName = "rex:YForm_selectData";
    var event = opener.jQuery.Event(eventName);
    if (event.isDefaultPrevented()) {
        self.close();
    }

    if(multiple == 1) {
        var datalist = "YFORM_DATASETLIST_SELECT_"+id;
        var source = opener.document.getElementById(datalist);
        var sourcelength = source.options.length;

        option = opener.document.createElement("OPTION");
        option.text = data_name;
        option.value = data_id;

        source.options.add(option, sourcelength);
        opener.writeREX(id, 'YFORM_DATASETLIST_', 'YFORM_DATASETLIST_SELECT_');
    }else {
        var data_field_name = 'YFORM_DATASET_'+id+'_NAME';
        var data_field_id = 'YFORM_DATASET_'+id;
        opener.document.getElementById(data_field_name).value = data_name;
        opener.document.getElementById(data_field_id).value = data_id;
        self.close();
    }
    opener.jQuery('body').trigger(eventName, [id, data_id, data_name, multiple]);
}
