<template>
    <div>
        <div class="header">
            <h1>{{ $t('ias.plan') }}</h1>
        </div>
        <div class="container">
            <div class="content">
                <div class="category_level">
                    <h3>
                        {{ category.app_name }} {{ $t('ias.plan') }}<span style="padding-left: 20px; font-size: 24px">{{
                                $t(category.price)
                        }}</span><span class="float-right pr-5">{{ $t(category.price) }}</span>
                    </h3>
                    <div v-if="locale == 'en'" class="float-right">include tax</div>
                </div>
                <div style="padding-left: 20px; padding-top: 30px">
                    <router-link :to="{ name: 'terms_and_conditions' }">
                        <h5>{{ $t('terms_and_conditions') }}</h5>
                    </router-link>
                </div>
                <div class="payment_method">
                    <h4>{{ $t('payment_method') }}</h4>
                    <div v-if="locale == 'jp'" class="row" style="padding-left: 20px">
                        <div class="form-group">
                            <form id="komoju-form" method="post">
                                <input id="komojuToken" type="hidden" v-model="komojuToken" name="komojuToken" />
                                <b-button variant="outline-primary" id="bank_transfer">{{ $t('bank_transfer') }}
                                </b-button>
                                <!-- <b-button variant="outline-primary" id="debit_card">{{ $t('debit_card') }}</b-button> -->
                                <b-button variant="outline-primary" id="paypal">{{ $t('paypal') }}</b-button>
                                <b-button variant="outline-primary" id="amazon_pay">{{ $t('amazon_pay') }}</b-button>
                                <b-button variant="outline-primary" id="credit_card">{{ $t('credit_card') }}</b-button>
                            </form>
                        </div>
                    </div>
                    <div v-else class="row" style="padding-left: 20px">
                        <div class="form-group">
                            <form @submit.prevent="paypal_payment_post" method="post">
                                <b-button variant="outline-primary" id="bank_transfer">{{ $t('bank_transfer') }}
                                </b-button>
                                <!-- <b-button variant="outline-primary" id="debit_card">{{ $t('debit_card') }}</b-button> -->
                                <b-button variant="outline-primary" id="paypal">{{ $t('paypal') }}</b-button>
                                <b-button variant="outline-primary" id="amazon_pay">{{ $t('amazon_pay') }}</b-button>
                                <b-button variant="outline-primary" id="credit_card"> {{ $t('credit_card') }}</b-button>

                            </form>
                        </div>
                    </div>

                    <div class="modal" id="paypal-modal">
                        <div class="modal-content">
                            <div id="paypal-container"></div>
                        </div>
                    </div>

                    <div class="modal" id="stripe-modal">
                        <div class="modal-content">
                            <div id="stripe-container">
                                <form id="payment-form">
                                    <input type="text" id="email" placeholder="Enter email address" />
                                    <div id="payment-element">

                                    </div>
                                    <button class="stripe_btn" id="submit">
                                        <div class="spinner hidden" id="spinner"></div>
                                        <span id="button-text">Pay now</span>
                                    </button>
                                    <div id="payment-message" class="hidden"></div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal" id="bank-modal">
                        <div class="modal-content">

                            <form id="bank-payment-form">
                                <div id="bank-payment">
                                    <!-- Elements will create form elements here -->
                                </div>
                                <button class="bank-btn" id="submit">Pay now</button>
                                <div id="error-message">
                                    <!-- Display error message to your customers here -->
                                </div>
                            </form>
                        </div>
                    </div>

                </div>


                <div class="form-group float-right">
                    <b-button variant="outline-primary" :to="{ name: $t('purchase.url') }">{{ $t('back') }}</b-button>
                    <b-button variant="outline-primary" id="subscribe">{{ $t('subscribe') }}</b-button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios'
import { mapGetters } from 'vuex'
import $ from 'jquery'
import swal from 'sweetalert2/dist/sweetalert2.js'
import process from 'process'

export default {
    data: () => ({
        // stripe: {},
        cardElement: {},
        customer: {
            name: '',
            email: '',
            //first_name: '',
            //last_name: '',
            //email: '',
            //address: '',
            //city: '',
            //state: '',
            //zip_code: ''
        },
        category: '',
        paymentProcessing: false,
        //payment form display
        form_display: 'komoju',
        komojuToken: '',
        amount: ''
    }),

    mounted() {
        var that = this;
        //paypal button section
        const script_paypal = document.createElement("script")
        const ClientID = process.env.MIX_PAYPAL_SANDBOX_CLIENT_ID
        const currency = this.$route.query.currency
        console.log(ClientID)

        script_paypal.setAttribute(`src`, `https://www.paypal.com/sdk/js?client-id=${ClientID}&currency=${currency}`)
        script_paypal.setAttribute(`data-namespace`, `paypal_sdk`)
        script_paypal.setAttribute(`data-sdk-integration-source`, `button-factory`)
        script_paypal.addEventListener("load", () => this.setLoaded())
        document.body.appendChild(script_paypal)
        //komoju payment button
        /**
         * Payment script has to be inserted at the head of page so they can detect any illegal actions of payment
         */
        // const script_komoju = document.createElement("script")

        // script_komoju.setAttribute(`src`, `https://multipay.komoju.com`)
        // document.body.appendChild(script_komoju)

        var payForm = document.getElementById("komoju-form")
        var amount = this.amount
        const komoju_pu_key = 'pk_9e17c1ee95a8ca65eafbca819189907a6d0ebd0c'

        var handler = Komoju.multipay.configure({
            key: komoju_pu_key,
            token: function (token) {
                console.log("this is komoju token")
                console.log(token.id)
                this.komojuToken = token.id
                this.komoju_purchase()
            }
        });

        $("#paypal").on("click", function () {
            $("#paypal-modal").css({ "display": "block" });
        })

        $("#paypal-modal").on("click", function (event) {
            if (event.target !== $("#paypal-modal .modal-content")) {
                // $("#paypal-modal").css({ "display": "none" })
                $('#paypal-modal').fadeOut(500);
            };

        })

        $("#credit_card").on("click", function () {
            $("#stripe-modal").css({ "display": "block" });
            that.stripeCheckout(that)

        })

        $("#stripe-modal").on("click", function (event) {
            console.log($(event.target).parent("#payment-form").length)
            // if (event.target.matches(".modal-content") || !event.target.closest("#stripe-modal")) {
            if (!$(event.target).parent("#payment-form").length) {
                $('#stripe-modal').fadeOut(500);
            }
        })

        $("#bank_transfer").on("click", function (e) {
            $("#bank-modal").css({ "display": "block" });
            that.stripeBank(that)
        });

        $("#bank-modal").on("click", function (event) {
            if (event.target !== $("#bank-modal .modal-content")) {
                // $("#paypal-modal").css({ "display": "none" })
                $('#bank-modal').fadeOut(500);
            };
        })

        // $("#credit_card").on("click", function (e) {
        //     handler.open({
        //         amount: amount,
        //         endpoint: "https://komoju.com",
        //         currency: "JPY",
        //         methods: [
        //             "credit_card"
        //         ],
        //     });
        //     e.preventDefault();
        //     $("#myModal").modal("show")
        // });

        // $("#bank_transfer").on("click", function (e) {
        //     handler.open({
        //         amount: amount,
        //         endpoint: "https://komoju.com",
        //         currency: "JPY",
        //         methods: [
        //             "bank_transfer"
        //         ],
        //     });
        //     e.preventDefault();
        // });

    },

    methods: {
        async getCategorydata(app_id, cat_id) {
            const { data } = await axios.get('/api/get/checkout/category/' + app_id + '/' + cat_id)
            this.category = data;

            console.log(this.category)
        },
        /**
         * 20220614_stripe card payment_golden
         */
        stripeCheckout(that) {
            // stripeCheckout: function () {
            var app_id = this.$route.query.app_id;
            var cat_id = this.$route.query.cat_id;
            var amount = this.amount
            var router = this.$router
            var currency = this.$route.query.currency;

            // This is your test publishable API key.
            const stripe_pb_key = process.env.MIX_STRIPE_KEY

            const stripe = Stripe(stripe_pb_key)

            let elements

            initialize()
            checkStatus()

            document
                .querySelector("#payment-form")
                .addEventListener("submit", handleSubmit)

            async function initialize() {
                const { clientSecret } = await fetch("/api/cardpay", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        amount: amount,
                        currency: currency,
                        email: that.user.email
                    }),
                    // }).then((r) => console.log(r.json()));
                }).then((r) => r.json())
                console.log(clientSecret)

                const options = {
                    clientSecret: clientSecret,
                    // Fully customizable with appearance API.
                    appearance: {
                        theme: 'stripe',

                        // variables: {
                        //     colorPrimary: '#0570de',
                        //     colorBackground: '#ffffff',
                        //     colorText: '#30313d',
                        //     colorDanger: '#df1b41',
                        //     fontFamily: 'Ideal Sans, system-ui, sans-serif',
                        //     spacingUnit: '2px',
                        //     borderRadius: '4px',
                        //     // See all possible variables below
                        // }
                    },
                };

                // Pass the appearance object to the Elements instance
                elements = stripe.elements(options)

                const paymentElement = elements.create("payment")
                paymentElement.mount("#payment-element")
            }

            async function handleSubmit(e) {

                e.preventDefault();
                setLoading(true);
                const { error } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        // Make sure to change this to your payment completion page
                        return_url: "http://localhost:8000/checkout/confirmation",
                        // return_url: "https://lifeanalytics.org/checkout/confirmation",
                        receipt_email: document.getElementById("email").value,
                    },
                });

                // This point will only be reached if there is an immediate error when
                // confirming the payment. Otherwise, your customer will be redirected to
                // your `return_url`. For some payment methods like iDEAL, your customer will
                // be redirected to an intermediate site first to authorize the payment, then
                // redirected to the `return_url`.
                if (error.type === "card_error" || error.type === "validation_error") {
                    showMessage(error.message);
                } else {
                    showMessage("An unexpected error occurred.");
                }

                setLoading(false);
            }

            async function checkStatus() {
                const clientSecret = new URLSearchParams(window.location.search).get(
                    "payment_intent_client_secret"
                );

                if (!clientSecret) {
                    return;
                }

                const { paymentIntent } = await stripe.retrievePaymentIntent(clientSecret);

                switch (paymentIntent.status) {
                    case "succeeded":
                        showMessage("Payment succeeded!");
                        break;
                    case "processing":
                        showMessage("Your payment is processing.");
                        break;
                    case "requires_payment_method":
                        showMessage("Your payment was not successful, please try again.");
                        break;
                    default:
                        showMessage("Something went wrong.");
                        break;
                }
            }

            // ------- UI helpers -------

            async function showMessage(messageText) {
                const messageContainer = document.querySelector("#payment-message");

                messageContainer.classList.remove("hidden");
                messageContainer.textContent = messageText;

                setTimeout(function () {
                    messageContainer.classList.add("hidden");
                    messageText.textContent = "";
                }, 4000);
            }

            // Show a spinner on payment submission
            async function setLoading(isLoading) {
                if (isLoading) {
                    // Disable the button and show a spinner
                    document.querySelector("#submit").disabled = true;
                    document.querySelector("#spinner").classList.remove("hidden");
                    document.querySelector("#button-text").classList.add("hidden");
                } else {
                    document.querySelector("#submit").disabled = false;
                    document.querySelector("#spinner").classList.add("hidden");
                    document.querySelector("#button-text").classList.remove("hidden");
                }
            }
        },

        stripeBank(that) {
            var app_id = this.$route.query.app_id;
            var cat_id = this.$route.query.cat_id;
            var amount = this.amount
            var router = this.$router
            var currency = this.$route.query.currency;

            // This is your test publishable API key.
            const stripe_pb_key = process.env.MIX_STRIPE_KEY

            const stripe = Stripe(stripe_pb_key, {
                betas: ['customer_balance_beta_1'],
            })

            let elements

            initialize()

            async function initialize() {
                alert(amount);
                const { clientSecret } = await fetch("/api/bankpay", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        amount: amount,
                        currency: currency,
                        user_id: that.user.id,
                        email: that.user.email,
                        name: that.user.name,
                        app_id: app_id,
                        cat_id: cat_id,

                    }),
                    // }).then((r) => console.log(r.json()));
                }).then((r) => r.json())
                const options = {
                    clientSecret: clientSecret,
                    // Fully customizable with appearance API.
                    appearance: {/*...*/ },
                };

                // Set up Stripe.js and Elements to use in checkout form, passing the client secret obtained in step 2
                elements = stripe.elements(options);

                // Create and mount the Payment Element
                const paymentElement = elements.create('payment');
                console.log('paymentElement ');
                console.log(paymentElement);
                paymentElement.mount('#bank-payment');

                const form = document.getElementById('bank-payment-form');

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const { error } = await stripe.confirmPayment({
                        //`Elements` instance that was used to create the Payment Element
                        elements,
                        confirmParams: {
                            // return_url: 'http://localhost:8000/checkout/confirmation',
                            return_url: 'https://lifeanalytics.org/checkout/confirmation',
                        },
                    });

                    if (error) {
                        // This point will only be reached if there is an immediate error when
                        // confirming the payment. Show error to your customer (for example, payment
                        // details incomplete)
                        const messageContainer = document.querySelector('#error-message');
                        messageContainer.textContent = error.message;
                    } else {
                        // Your customer will be redirected to your `return_url`. For some payment
                        // methods like iDEAL, your customer will be redirected to an intermediate
                        // site first to authorize the payment, then redirected to the `return_url`.
                    }
                });
            }
        },

        //paypal button
        setLoaded: function () {
            console.log("paypal_button")
            var user_id = this.user.id
            var app_id = this.$route.query.app_id;
            var cat_id = this.$route.query.cat_id;
            var amount = this.amount
            var router = this.$router
            var currency = this.$route.query.currency;

            // console.log(app_id)
            console.log(cat_id)
            console.log(amount)

            paypal_sdk.Buttons({
                style: {
                    shape: 'pill',
                    label: 'checkout',
                    size: 'small',
                    color: 'gold',
                    layout: 'vertical'
                },

                createOrder: function (data, actions) {
                    return actions.order.create({
                        purchase_units: [
                            {
                                amount: {
                                    currency_code: currency,
                                    value: amount
                                }
                            }
                        ]
                    });
                },

                onApprove: function (data, actions) {
                    console.log(data)
                    return actions.order.capture().then(function (orderData) {

                        // Full available details
                        console.log('Capture result', orderData, JSON.stringify(orderData, null, 2));

                        //Save transaction history  
                        if (orderData.status == 'COMPLETED') {
                            axios.post('/api/payment/checkout/transaction/save/', {
                                transaction: orderData.purchase_units[0].payments.captures[0],
                                payer: orderData.payer,
                                payee: orderData.purchase_units[0].payee,
                                user_id: user_id,
                                app_id: app_id,
                                cat_id: cat_id,
                            }).then((res) => {
                                console.log(res)
                                const data = res.data
                                router.push({ name: 'confirmation', query: { transaction_id: res.data } })
                            })
                        }

                        // Or go to another URL:  actions.redirect('thank_you.html');

                    });
                },

                onError: function (err) {
                    console.log(err);
                }

            }).render("#paypal-container")
        },

        async komoju_purchase() {

            console.log("komoju button successful pass")

            var user_id = this.user.id
            var app_id = this.category.id
            var amount = this.amount
            var router = this.$router
            var token = this.komojuToken

            var querystring = require('querystring')
            //var https = require('https')
            var secret_key = 'sk_123456'
            var auth = 'Basic ' + Buffer.from(secret_key + ':').toString('base64')

            var komoju_post_data = querystring.stringify({
                'amount': amount,
                'currency': 'JPY',
                'payment_details': token
            })

            console.log(komoju_post_data)

            var post_options = {
                host: 'komoju.com',
                port: '443',
                path: '/api/v1/payments',
                method: 'POST',
                headers: {
                    'Authorization': auth,
                    'Content-Length': Buffer.byteLength(komoju_post_data)
                }
            }
            console.log("komoju post option success")

            var post_req = axios.request(post_options, function (res) {
                res.setEncoding('utf8')
                res.on('data', function (chunk) {
                    console.log(chunk)
                })
            })
        },

        async furikomi_payment_post() {
            this.paymentProcessing = true
            var { data } = await axios.post('/api/payment/furikomi/checkout/send', {
                email: this.customer.email,
                price: this.category.price
            })
            this.paymentProcessing = false
        }
    },
    created() {
        var app_id = this.$route.query.app_id;
        var cat_id = this.$route.query.cat_id;
        this.amount = this.$route.query.amount;
        this.getCategorydata(app_id, cat_id)
        this.customer.name = this.user.name
        this.customer.email = this.user.email
        this.locale = this.$root.$i18n.locale
    },
    computed: mapGetters({
        user: 'auth/user'
    })
}
</script>

<style scoped>
.header {
    width: 100%;
    background-color: #211F40;
    height: 150px;
}

.header h1 {
    padding-top: 3rem;
    text-align: center;
    color: #fff;
    font-weight: 600;
}

.content {
    width: 100%;
    padding-top: 50px;
}

.content .category_level h4,
.content .payment_method h4,
.content .payment_information_form h4 {
    font-weight: 700;
    padding: 15px 0;
}

.content .category_level h6 {
    padding: 10px 0;
}

.content .payment_method label {
    display: flex;
}

.content .payment_method label img {
    padding-left: 10px;
    display: inline;
    height: 55px;
}

.content .payment_information_form .paypal-button {
    display: flex;
    justify-content: space-around;
}

.content .payment_information_form .paypal-button div {
    width: 50%;
}

.btn-outline-primary {
    border-radius: 10px;
}

#paypal-modal {
    background-color: rgba(0, 0, 0, 0.5);
}

#paypal-modal .modal-content {
    width: 400px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 20px;
    border-radius: 10px;
}

#stripe-modal {
    background-color: rgba(0, 0, 0, 0.5);
}

#stripe-modal .modal-content {
    width: 32%;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 20px;
    border-radius: 10px;
}

#bank-modal {
    background-color: rgba(0, 0, 0, 0.5);
}

#bank-modal .modal-content {
    width: 32%;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 20px;
    border-radius: 10px;
}

/* stripe_style */
#payment-form {
    width: 30vw;
    min-width: 500px;
    align-self: center;
    box-shadow: 0px 0px 0px 0.5px rgba(50, 50, 93, 0.1),
        0px 2px 5px 0px rgba(50, 50, 93, 0.1), 0px 1px 1.5px 0px rgba(0, 0, 0, 0.07);
    border-radius: 7px;
    padding: 40px;
}

.hidden {
    display: none;
}

#email {
    border-radius: 6px;
    margin-bottom: 16px;
    padding: 12px;
    border: 1px solid rgba(50, 50, 93, 0.1);
    max-height: 44px;
    font-size: 16px;
    width: 100%;
    background: white;
    box-sizing: border-box;
}

#payment-message {
    color: rgb(105, 115, 134);
    font-size: 16px;
    line-height: 20px;
    padding-top: 12px;
    text-align: center;
}

#payment-element {
    margin-bottom: 24px;
}

/* Buttons and links */

.bank-btn {
    margin-top: 20px;
    background: #5469d4;
    font-family: Arial, sans-serif;
    color: #ffffff;
    border-radius: 4px;
    border: 0;
    padding: 12px 16px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    display: block;
    transition: all 0.2s ease;
    box-shadow: 0px 4px 5.5px 0px rgba(0, 0, 0, 0.07);
    width: 100%;
}

.stripe_btn {
    background: #5469d4;
    font-family: Arial, sans-serif;
    color: #ffffff;
    border-radius: 4px;
    border: 0;
    padding: 12px 16px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    display: block;
    transition: all 0.2s ease;
    box-shadow: 0px 4px 5.5px 0px rgba(0, 0, 0, 0.07);
    width: 100%;
}

.stripe_btn:hover {
    filter: contrast(115%);
}

.stripe_btn:disabled {
    opacity: 0.5;
    cursor: default;
}

/* spinner/processing state, errors */
.spinner,
.spinner:before,
.spinner:after {
    border-radius: 50%;
}

.spinner {
    color: #ffffff;
    font-size: 22px;
    text-indent: -99999px;
    margin: 0px auto;
    position: relative;
    width: 20px;
    height: 20px;
    box-shadow: inset 0 0 0 2px;
    -webkit-transform: translateZ(0);
    -ms-transform: translateZ(0);
    transform: translateZ(0);
}

.spinner:before,
.spinner:after {
    position: absolute;
    content: "";
}

.spinner:before {
    width: 10.4px;
    height: 20.4px;
    background: #5469d4;
    border-radius: 20.4px 0 0 20.4px;
    top: -0.2px;
    left: -0.2px;
    -webkit-transform-origin: 10.4px 10.2px;
    transform-origin: 10.4px 10.2px;
    -webkit-animation: loading 2s infinite ease 1.5s;
    animation: loading 2s infinite ease 1.5s;
}

.spinner:after {
    width: 10.4px;
    height: 10.2px;
    background: #5469d4;
    border-radius: 0 10.2px 10.2px 0;
    top: -0.1px;
    left: 10.2px;
    -webkit-transform-origin: 0px 10.2px;
    transform-origin: 0px 10.2px;
    -webkit-animation: loading 2s infinite ease;
    animation: loading 2s infinite ease;
}

@-webkit-keyframes loading {
    0% {
        -webkit-transform: rotate(0deg);
        transform: rotate(0deg);
    }

    100% {
        -webkit-transform: rotate(360deg);
        transform: rotate(360deg);
    }
}

@keyframes loading {
    0% {
        -webkit-transform: rotate(0deg);
        transform: rotate(0deg);
    }

    100% {
        -webkit-transform: rotate(360deg);
        transform: rotate(360deg);
    }
}

@media only screen and (max-width: 600px) {
    form {
        width: 80vw;
        min-width: initial;
    }
}
</style>