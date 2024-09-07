$(document).on('rex:ready',function() {

    var monthNames = [
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
    ];

    var daysOfWeek = [
        "Su",
        "Mo",
        "Tu",
        "We",
        "Th",
        "Fr",
        "Sa"
    ];

    $("input[data-yform-tools-inputmask]").each(function () {
        var format = $(this).attr('data-yform-tools-inputmask');
        if (format !== "") {
            format = format.toLowerCase();
            $(this).inputmask(format);
        } else {
            $(this).inputmask();
        }
    });

    $("input[data-yform-tools-datepicker]").each(function () {
        var format = $(this).attr('data-yform-tools-datepicker');
        if (format !== "") {
            $(this).daterangepicker({
                "autoApply": true,
                "autoUpdateInput": false,
                "singleDatePicker": true,
                "showDropdowns": true,
                "showWeekNumbers": true,
                "showISOWeekNumbers": true,
                "showCustomRangeLabel": false,
                "locale": {
                    "format": format,
                    "separator": " - ",
                    "weekLabel": "W",
                    "daysOfWeek": daysOfWeek,
                    "monthNames": monthNames,
                    "firstDay": 1
                },
                "ranges": {
                    "Today": [moment(), moment()],
                    "Yesterday": [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    "First of this month": [moment().startOf('month'), moment().startOf('month')],
                    "First of next month": [moment().add(1, "months").startOf('month'), moment().add(1, "months").startOf('month')],
                    "First of next year": [moment().add(1, 'year').startOf('year'), moment().add(1, 'year').endOf('year')],
                    "In 6 months": [moment().add(6, 'months'), moment().add(6, 'months')],
                    "In 1 year": [moment().add(1, 'year'), moment().add(1, 'year')],
                    "In 2 years": [moment().add(2, 'year'), moment().add(2, 'year')],
                    "In 5 years": [moment().add(5, 'year'), moment().add(5, 'year')],
                },
            }, function (start, end, label) {
                // console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");
            })
                .on('apply.daterangepicker', function(e, picker) {
                    var format = $(this).attr('data-yform-tools-datepicker');
                    if (format !== "") {
                        $(this).val(picker.startDate.format(format));
                    }
                });
        }
    });

    $("input[data-yform-tools-datetimepicker]").each(function () {
        var format = $(this).attr('data-yform-tools-datetimepicker');
        if (format !== "") {
            format = format.replace("ii", "mm"); // ii -> mm
            $(this).daterangepicker({
                "autoUpdateInput": false,
                "timePicker": true,
                "timePicker24Hour": true,
                "timePickerSeconds": true,
                "singleDatePicker": true,
                "showDropdowns": true,
                "showWeekNumbers": true,
                "showISOWeekNumbers": true,
                "locale": {
                    "format": format,
                    "separator": " - ",
                    "weekLabel": "W",
                    "daysOfWeek": daysOfWeek,
                    "monthNames": monthNames,
                    "firstDay": 1
                },
            }, function(start, end, label) {
                // console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");
            })
                .on('apply.daterangepicker', function(e, picker) {
                    format = $(this).attr('data-yform-tools-datetimepicker');
                    format = format.replace("ii", "mm");
                    if (format !== "") {
                        $(this).val(picker.startDate.format(format));
                    }
                });
        }
    });

    $("input[data-yform-tools-datetimerangepicker]").each(function () {
        var format = $(this).attr('data-yform-tools-datetimerangepicker');
        if (format !== "") {
            format = format.replace("ii", "mm");
            $(this).daterangepicker({
                "autoUpdateInput": false,
                "timePicker": true,
                "timePicker24Hour": true,
                "timePickerSeconds": true,
                "showDropdowns": true,
                "showWeekNumbers": true,
                "showISOWeekNumbers": true,
                "linkedCalendars": false,
                "ranges": {
                    "Today": [moment(), moment()],
                    "Yesterday": [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    "Last 7 Days": [moment().subtract(6, 'days'), moment()],
                    "Last 30 Days": [moment().subtract(29, 'days'), moment()],
                    "This Month": [moment().startOf('month'), moment().endOf('month')],
                    "Last Month": [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    "This Year": [moment().startOf('year'), moment().endOf('year')],
                    "Last Year": [moment().subtract(365, 'days').startOf('year'), moment().subtract(365, 'days').endOf('year')],
                },
                "locale": {
                    "format": format,
                    "separator": " - ",
                    "weekLabel": "W",
                    "daysOfWeek": daysOfWeek,
                    "monthNames": monthNames,
                    "firstDay": 1
                }
            }, function (start, end, label) {
                // console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");
            }).on('apply.daterangepicker', function(ev, picker) {
                format = $(this).attr('data-yform-tools-datetimerangepicker');
                format = format.replace("ii", "mm");
                if (format !== "") {
                    $(this).val(picker.startDate.format(format) + ' - ' + picker.endDate.format(format));
                }
            }).on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        }
    });

    $("input[data-yform-tools-daterangepicker]").each(function () {
        var format = $(this).attr('data-yform-tools-daterangepicker');
        if (format !== "") {
            $(this).daterangepicker({
                "autoApply": true,
                "autoUpdateInput": false,
                "showDropdowns": true,
                "showWeekNumbers": true,
                "showISOWeekNumbers": true,
                "linkedCalendars": false,
                "ranges": {
                    "Today": [moment(), moment()],
                    "Yesterday": [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    "Last 7 Days": [moment().subtract(6, 'days'), moment()],
                    "Last 30 Days": [moment().subtract(29, 'days'), moment()],
                    "This Month": [moment().startOf('month'), moment().endOf('month')],
                    "Last Month": [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    "This Year": [moment().startOf('year'), moment().endOf('year')],
                    "Last Year": [moment().subtract(365, 'days').startOf('year'), moment().subtract(365, 'days').endOf('year')],
                },
                "locale": {
                    "format": format,
                    "separator": " - ",
                    "weekLabel": "W",
                    "daysOfWeek": daysOfWeek,
                    "monthNames": monthNames,
                    "firstDay": 1
                }
            }, function (start, end, label) {
                // console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");
            }).on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format(format) + ' - ' + picker.endDate.format(format));
            }).on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        }
    });
});
