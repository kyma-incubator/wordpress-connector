jQuery(document).ready(function($) {
    $("#kymaconnectbtn").click(function() {
        // TODO: show a spinner
        // TODO: disable button
        var this2 = this;
        let url = document.getElementById('kyma-connect-url').value;
        $.post(ajaxurl, {
            _ajax_nonce: kyma_ajax_vars.connectnonce,
            action: "connect_to_kyma",
            url: url
        }, function(data) {
            // TODO: hide spinner
            console.log(data);
            if (data.success === false) {
                displayNotice('notice-error', data.data[0].message || ("Unknown error, code " + data.data[0].code));
                return;
            }

            displayNotice('notice-success', 'Successfully connected to Kyma');
        });
    });

    $("#kymadisconnectbtn").click(function() {
        // TODO: show a spinner
        // TODO: disable button
        var this2 = this;
        $.post(ajaxurl, {
            _ajax_nonce: kyma_ajax_vars.disconnectnonce,
            action: "disconnect_from_kyma",
        }, function(data) {
            // TODO: hide spinner
            console.log(data);
            if (data.success === false) {
                displayNotice('notice-error', data.data[0].message || ("Unknown error, code " + data.data[0].code));
                return;
            }

            displayNotice('notice-success', 'Successfully disconnected from Kyma');
        });
    });
});

function displayNotice(className, message) {
    var notice = document.createElement('div');
    notice.classList = 'notice ' + className;
    notice.innerHTML = '<p>' + message + '</p>';
    document.getElementById('kymanotices').appendChild(notice);
}
