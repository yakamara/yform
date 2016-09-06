$(document).on('ready pjax:success',function() {

    $("select[data-yform-tools-select2]").each(function () {
        $(this).select2({
              theme: "bootstrap"
          }
        );
    });

    $("input[data-yform-tools-inputmask]").each(function () {
        var format = $(this).attr('data-yform-tools-inputmask');
        if (format != "") {
            format = format.toLowerCase();
            $(this).inputmask(format);
        }
    });

    $("input[data-yform-tools-datepicker]").each(function () {
        var format = $(this).attr('data-yform-tools-datepicker');
        if (format != "") {
            $(this).daterangepicker({
                "singleDatePicker": true,
                "showDropdowns": true,
                "showWeekNumbers": true,
                "showISOWeekNumbers": true,
                "autoApply": true,
                "locale": {
                    "format": format,
                    "separator": " - ",
                    "weekLabel": "W",
                    "daysOfWeek": [
                        "Su",
                        "Mo",
                        "Tu",
                        "We",
                        "Th",
                        "Fr",
                        "Sa"
                    ],
                    "monthNames": [
                        "January",
                        "February",
                        "March",
                        "April",
                        "May",
                        "June",
                        "July",
                        "August",
                        "September",
                        "October",
                        "November",
                        "December"
                    ],
                    "firstDay": 1
                }
            }, function (start, end, label) {
                // console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");
            });
        }
    });

    $("input[data-yform-tools-datetimepicker]").each(function () {
        var format = $(this).attr('data-yform-tools-datetimepicker');
        if (format != "") {
            // ii -> mm
            var format = format.replace("ii", "mm");
            $(this).daterangepicker({
                "timePicker": true,
                "timePicker24Hour": true,
                "timePickerSeconds": true,
                "singleDatePicker": true,
                "showDropdowns": true,
                "showWeekNumbers": true,
                "showISOWeekNumbers": true,
                "autoApply": true,
                "locale": {
                    "format": format,
                    "separator": " - ",
                    "weekLabel": "W",
                    "daysOfWeek": [
                        "Su",
                        "Mo",
                        "Tu",
                        "We",
                        "Th",
                        "Fr",
                        "Sa"
                    ],
                    "monthNames": [
                        "January",
                        "February",
                        "March",
                        "April",
                        "May",
                        "June",
                        "July",
                        "August",
                        "September",
                        "October",
                        "November",
                        "December"
                    ],
                    "firstDay": 1
                }
            }, function(start, end, label) {
                // console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");
            });
        }
    });

});