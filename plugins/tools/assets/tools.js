$(document).on('ready pjax:success',function() {

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

    $("select[data-yform-tools-select2]").each(function () {
        var options = $(this).attr('data-yform-tools-select2');
        var placeholder = $(this).attr("placeholder");
        if (options == "tags") {
            options = {"theme":"bootstrap", placeholder: placeholder, allowClear: true, tags: true, tokenSeparators: [','] };
        } else {
            options = {"theme":"bootstrap", placeholder: placeholder, allowClear: true };
        }
        $(this).select2(options);
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
                  autoUpdateInput: false,
                  singleDatePicker: true,
                  showDropdowns: true,
                  showWeekNumbers: true,
                  showISOWeekNumbers: true,
                  autoApply: true,
                  locale: {
                      format: format,
                      separator: " - ",
                      weekLabel: "W",
                      daysOfWeek: daysOfWeek,
                      monthNames: monthNames,
                      firstDay: 1
                  }
              }, function (start, end, label) {
                  // console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");
              })
              .on('apply.daterangepicker', function(e, picker) {

                  var format = $(this).attr('data-yform-tools-datepicker');
                  if (format != "") {
                      $(this).val(picker.startDate.format(format));
                  }

              });

        }
    });

    $("input[data-yform-tools-datetimepicker]").each(function () {
        var format = $(this).attr('data-yform-tools-datetimepicker');
        if (format != "") {
            format = format.replace("ii", "mm"); // ii -> mm
            $(this).daterangepicker({
                  timePicker: true,
                  timePicker24Hour: true,
                  timePickerSeconds: true,
                  singleDatePicker: true,
                  showDropdowns: true,
                  showWeekNumbers: true,
                  showISOWeekNumbers: true,
                  autoApply: true,
                  locale: {
                      format: format,
                      separator: " - ",
                      weekLabel: "W",
                      daysOfWeek: daysOfWeek,
                      monthNames: monthNames,
                      firstDay: 1
                  }
              }, function(start, end, label) {
                  // console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");
              })
              .on('apply.daterangepicker', function(e, picker) {
                  format = $(this).attr('data-yform-tools-datetimepicker');
                  format = format.replace("ii", "mm");
                  if (format != "") {
                      $(this).val(picker.startDate.format(format));
                  }

              });
        }
    });

    $("input[data-yform-tools-datetimerangepicker]").each(function () {
        var format = $(this).attr('data-yform-tools-datetimerangepicker');
        if (format != "") {
            var format = format.replace("ii", "mm");
            $(this).daterangepicker({
                timePicker: true,
                timePicker24Hour: true,
                timePickerSeconds: true,
                autoUpdateInput: false,
                showDropdowns: true,
                showWeekNumbers: true,
                showISOWeekNumbers: true,
                linkedCalendars: false,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                    'Last Year': [moment().subtract(365, 'days').startOf('year'), moment().subtract(365, 'days').endOf('year')],
                },
                locale: {
                    format: format,
                    separator: " - ",
                    weekLabel: "W",
                    daysOfWeek: daysOfWeek,
                    monthNames: monthNames,
                    firstDay: 1
                }
            }, function (start, end, label) {
                // console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");

            }).on('apply.daterangepicker', function(ev, picker) {
                format = $(this).attr('data-yform-tools-datetimerangepicker');
                format = format.replace("ii", "mm");
                if (format != "") {
                    $(this).val(picker.startDate.format(format) + ' - ' + picker.endDate.format(format));
                }

            }).on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');

            });
        }
    })

    $("input[data-yform-tools-daterangepicker]").each(function () {
        format = $(this).attr('data-yform-tools-daterangepicker');
        if (format != "") {
            $(this).daterangepicker({
                autoUpdateInput: false,
                showDropdowns: true,
                showWeekNumbers: true,
                showISOWeekNumbers: true,
                linkedCalendars: false,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                    'Last Year': [moment().subtract(365, 'days').startOf('year'), moment().subtract(365, 'days').endOf('year')],
                },
                locale: {
                    format: format,
                    separator: " - ",
                    weekLabel: "W",
                    daysOfWeek: daysOfWeek,
                    monthNames: monthNames,
                    firstDay: 1
                }
            }, function (start, end, label) {
                // console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");

            }).on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format(format) + ' - ' + picker.endDate.format(format));

            }).on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');

            });
        }
    })

});