(function(){
    var script = document.querySelector('script[data-countdown][data-plugin="pbs.logout"]');
    var minutes = script.getAttribute('data-minutes');

    if (!minutes || minutes == 0) {
        return;
    }

    clearTimeout(frontendTimer);

    function startTimer() {
        return setTimeout(function() {
            $.request('onLogoutUser', {
                success: function (data) {
                    if (data.logged_out) {
                        window.location.reload();
                    } else {
                        startTimer();
                    }
                }
            });
        }, minutes * 60 * 1200);
    }
    var frontendTimer = startTimer();

})();