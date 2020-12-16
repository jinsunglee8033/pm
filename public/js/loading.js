var myApp;
myApp = myApp || (function () {
    return {
        showPleaseWait: function (mode) {
            if (mode == 'new') {
                $('.pager').hide();
            } else {
                $('.modal-footer').hide();
            }

            $('.progress.in-form').show();
        },
        hidePleaseWait: function (mode) {
            if (mode == 'new') {
                $('.pager').show();
            } else {
                $('.modal-footer').show();
            }
            $('.progress.in-form').hide();
        },
        showLoading: function() {
            $('#loading-modal').modal('show');
        },
        hideLoading: function() {
            $('#loading-modal').modal('hide');
        },
        showError: function(msg) {
            $('#error-modal-footer').show();

            $('#error-modal-title').text('Error!');
            $('#error-modal-title').css('color', 'red');
            $('#error-modal-body').html(msg);
            $('#error-modal').modal('show');

        },
        showSuccess: function(msg, func) {
            $('#error-modal-footer').show();

            $('#error-modal-title').text('Success!');
            $('#error-modal-title').css('color', 'green');
            $('#error-modal-body').text(msg);
            $('#error-modal').modal('show');

            $('#error-modal').on('hidden.bs.modal', function() {
                $('#error-modal').off();
                if (func) {
                    func();
                }
            });
        },
        showConfirm: function(body, ok, cancel) {
            $('#confirm-modal-footer').show();

            var title = 'Please Confirm';
            $('#confirm-modal-title').text(title);
            $('#confirm-modal-body').html(body);
            $('#confirm-modal').modal();

            $('#confirm-modal-cancel').on('click', function() {
                $('#confirm-modal-cancel').off();
                $('#confirm-modal').modal('hide');
                if (cancel) {
                    return cancel();
                }
            });
            $('#confirm-modal-ok').on('click', function() {
                $('#confirm-modal-ok').off();
                $('#confirm-modal').modal('hide');
                if (ok) {
                    return ok();
                }
            });
        },
        showMsg: function(msg, modal_to_close, is_error, reload) {
            if (is_error == '1') {
                myApp.showError(msg);
            } else {
                $('#' + modal_to_close).modal('hide');
                myApp.showSuccess(msg, function() {
                    if(reload == '1') {
                        window.location.reload();
                        console.log('here')
                    }
                })
            }
        }
    };
})();


$('input').on('keyup', function(e) {
    var max = $(this).prop('maxlength');
    if (max > 0) {
        if ($(this).val().length > max) {
            $(this).val($(this).val().substr(0, max));
        }
    }
});

var validRoutingNumber = function(routing) {

    // all valid routing numbers are 9 numbers in length
    if (routing.length !== 9) {
        return false;
    }

    // if it aint a number, it aint a routin' number
    if ( !$.isNumeric( routing ) ) {
        return false;
    }

    // routing numbers starting with 5 are internal routing numbers
    // usually found on bank deposit slips
    if ( routing[0] === '5' ) {
        return false;
    }

    // http://en.wikipedia.org/wiki/Routing_transit_number#MICR_Routing_number_format
    var checksumTotal = (7 * (parseInt(routing.charAt(0),10) + parseInt(routing.charAt(3),10) + parseInt(routing.charAt(6),10))) +
        (3 * (parseInt(routing.charAt(1),10) + parseInt(routing.charAt(4),10) + parseInt(routing.charAt(7),10))) +
        (9 * (parseInt(routing.charAt(2),10) + parseInt(routing.charAt(5),10) + parseInt(routing.charAt(8),10)));

    var checksumMod = checksumTotal % 10;

    if (checksumMod !== 0) {
        return false;
    } else {
        return true;
    }
};