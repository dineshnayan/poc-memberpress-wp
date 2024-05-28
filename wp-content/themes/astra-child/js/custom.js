jQuery(document).ready(function($) {
    $('#stripe-pay-button').on('click', function() {
        if (!premiumContentAjax.is_user_logged_in) {
            // Redirect to the login page
            window.location.href = premiumContentAjax.login_url + '?redirect_to=' + encodeURIComponent(window.location.href);
            return;
        }

        $.ajax({
            url: premiumContentAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'handle_payment',
                referrer: window.location.href
            },
            success: function(response) {
                if (response.success) {
                    var stripe = Stripe('pk_test_51PGEyXSHUOngqgQltGt39PNfw5KU4Zereh9EwQ5JMDDMBetfEIsMjVJzTnBQFmALtDeKXqqeAlZARC7UZmnEJDXY00VJXZzrMp');
                    stripe.redirectToCheckout({ sessionId: response.data.id });
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
});