
$(document).on('rex:ready',function() {

    $(".yform-dataset-widget").each(function () {
        let id = this.dataset.id;
        let link = this.dataset.link;
        let widget_type = this.dataset.widget_type;
        let field_name = this.dataset.field_name;

        $(this).find("a").each(function () {

            let multiple= 1;
            if (widget_type === "single") {
                multiple = 0;
            }

            // widget pool -
            if (this.classList.contains('yform-dataset-widget-pool')) {
                this.onclick = function () {
                    return newPoolWindow( link);
                };
            }

            // open
            if (this.classList.contains('yform-dataset-widget-open')) {
                this.onclick = function () {
                    let newWindowLink = link + '&rex_yform_manager_opener[id]='+id+'&rex_yform_manager_opener[field]='+field_name+'&rex_yform_manager_opener[multiple]='+multiple;
                    return newWindow( id, newWindowLink, 1200,800,',status=yes,resizable=yes');
                };
            }

            // delete
            if (this.classList.contains('yform-dataset-widget-delete')) {
                this.onclick = function () {

                    let viewObject = document.querySelector('#yform-dataset-view-'+id);
                    let realObject = document.querySelector('#yform-dataset-real-'+id);

                    if (multiple === 1) {
                        for (let position = 0; position < viewObject.options.length; position++) {
                            if (viewObject.options[position].selected) {
                                viewObject.options[position].remove();

                                if(position === 0) {
                                    if(viewObject.options.length > 0) {
                                        viewObject.options[0].selected = "selected";
                                    }
                                } else {
                                    if(viewObject.options.length > position) {
                                        viewObject.options[position].selected= "selected";
                                    } else {
                                        viewObject.options[position-1].selected= "selected";
                                    }
                                }

                                realObject.value = "";
                                for (let i=0; i < viewObject.options.length; i++) {
                                    realObject.value += (viewObject[i].value);
                                    if (viewObject.options.length > (i+1))  realObject.value += ',';
                                }

                                break;
                            }
                        }

                    } else {
                        viewObject.value = '';
                        realObject.value = '';
                    }
                };
            }

            // move up and down
            if (this.classList.contains('yform-dataset-widget-move')) {
                this.onclick = function () {

                    let viewObject = document.querySelector('#yform-dataset-view-'+id);
                    let realObject = document.querySelector('#yform-dataset-real-'+id);

                    for (let position = 0; position < viewObject.options.length; position++) {

                        if (viewObject.options[position].selected) {
                            if (this.classList.contains('yform-dataset-widget-move-up')) {
                                if(position > 0) {
                                    let option_temp_value = viewObject.options[position-1].value;
                                    let option_temp_text = viewObject.options[position-1].text;
                                    viewObject.options[position-1].value = viewObject.options[position].value;
                                    viewObject.options[position-1].text = viewObject.options[position].text;
                                    viewObject.options[position-1].selected= "selected";
                                    viewObject.options[position].value = option_temp_value;
                                    viewObject.options[position].text = option_temp_text;
                                }
                            }
                            if (this.classList.contains('yform-dataset-widget-move-down')) {
                                if(position < (viewObject.options.length-1)) {
                                    let option_temp_value = viewObject.options[position+1].value;
                                    let option_temp_text = viewObject.options[position+1].text;
                                    viewObject.options[position+1].value = viewObject.options[position].value;
                                    viewObject.options[position+1].text = viewObject.options[position].text;
                                    viewObject.options[position+1].selected= "selected";
                                    viewObject.options[position].value = option_temp_value;
                                    viewObject.options[position].text = option_temp_text;
                                }
                            }
                            if (this.classList.contains('yform-dataset-widget-move-top')) {
                                let option_temp_value = viewObject.options[position].value;
                                let option_temp_text = viewObject.options[position].text;
                                for(let i = position; i > 0; i--) {
                                    viewObject.options[i].value = viewObject.options[i-1].value;
                                    viewObject.options[i].text = viewObject.options[i-1].text;
                                }
                                viewObject.options[0].value = option_temp_value;
                                viewObject.options[0].text = option_temp_text;
                                viewObject.options[0].selected= "selected";
                            }
                            if (this.classList.contains('yform-dataset-widget-move-bottom')) {
                                let option_temp_value = viewObject.options[position].value;
                                let option_temp_text = viewObject.options[position].text;
                                for(let i = position; i < (viewObject.options.length-1); i++) {
                                    viewObject.options[i].value = viewObject.options[i+1].value;
                                    viewObject.options[i].text = viewObject.options[i+1].text;
                                }
                                viewObject.options[viewObject.options.length-1].value = option_temp_value;
                                viewObject.options[viewObject.options.length-1].text = option_temp_text;
                            }

                            realObject.value = "";
                            for (let i=0; i < viewObject.options.length; i++) {
                                realObject.value += (viewObject[i].value);
                                if (viewObject.options.length > (i+1))  realObject.value += ',';
                            }

                            // !!!
                            break;
                        }
                    }
                };
            }

            // set
            if (this.classList.contains('yform-dataset-widget-set')) {
                this.onclick = function () {

                    let id = this.dataset.id;
                    let opener_id = this.dataset.opener_id;
                    let opener_field = this.dataset.opener_field;
                    let multiple = this.dataset.multiple;
                    let value = this.dataset.value;

                    var event = new CustomEvent('rex:YForm_selectData', {
                        detail: {
                            id: id,
                            value: value,
                            multiple: multiple
                          }
                    })
                    opener.dispatchEvent(event)
                    if (!event?.defaultPrevented) {
                        self.close()
                    }

                    /** deprecated jQuery implementation – use native implementation above */
                    var event = opener.jQuery.Event('rex:YForm_selectData')
                    opener.jQuery(window).trigger(event, [id, value, multiple])
                    if (!event.isDefaultPrevented()) {
                        self.close()
                    }
                    
                    if(multiple == "1") {
                        let viewObject = opener.document.getElementById('yform-dataset-view-'+opener_id);
                        let option = opener.document.createElement("OPTION");
                        option.text = value;
                        option.value = id;
                        viewObject.options.add(option, viewObject.options.length);

                        let realObject = opener.document.getElementById('yform-dataset-real-'+opener_id);
                        realObject.value = "";

                        for (let i=0; i < viewObject.options.length; i++) {
                            realObject.value += (viewObject[i].value);
                            if (viewObject.options.length > (i+1))  realObject.value += ',';
                        }

                    } else {
                        opener.document.getElementById('yform-dataset-view-'+opener_id).value = value;
                        opener.document.getElementById('yform-dataset-real-'+opener_id).value = id;
                        self.close();
                    }

                }

            }

        });

    });

});
