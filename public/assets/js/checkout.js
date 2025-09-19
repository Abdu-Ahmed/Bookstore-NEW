// Simple helper used on checkout page or cart page to request a create session and redirect.
(async function () {
    window.createStripeSessionAndRedirect = async function (opts = {}) {
        // opts { endpoint: '/checkout/create-session', useStripeJS: false, publishableKey: null }
        const endpoint = opts.endpoint || (window.BASE_URL || '') + '/checkout/create-session';
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            });
            const data = await res.json();
            if (data.error) {
                alert(data.message || 'Unable to create checkout session');
                return;
            }

            // If backend returned an absolute url, redirect directly
            if (data.url) {
                window.location.href = data.url;
                return;
            }

            // fallback to Stripe.js redirectToCheckout (requires publishable key)
            if (typeof Stripe === 'undefined') {
                alert('Stripe.js not loaded and no direct checkout URL provided.');
                console.error('Missing Stripe.js and no session url returned', data);
                return;
            }

            if (!data.sessionId) {
                alert('No session ID returned from server.');
                console.error('No sessionId', data);
                return;
            }

            const publishableKey = opts.publishableKey || window.STRIPE_PUBLISHABLE_KEY || null;
            if (!publishableKey) {
                alert('Stripe publishable key is not configured.');
                return;
            }

            const stripe = Stripe(publishableKey);
            const result = await stripe.redirectToCheckout({ sessionId: data.sessionId });
            if (result.error) {
                alert(result.error.message);
            }
        } catch (err) {
            console.error('Checkout create failed', err);
            alert('Checkout request failed. See console for details.');
        }
    };
})();