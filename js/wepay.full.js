'use strict';

// initialize WePay object
var WePay = window.WePay = WePay || {};

WePay.PRODUCTION_ENDPOINT = "https://api.wepay.com";
WePay.STAGING_ENDPOINT    = "https://stage-api.wepay.com";

// Doc upload endpoint
WePay.PRODUCTION_UPLOAD_ENDPOINT = "https://uploads.wepay.com";
WePay.STAGING_UPLOAD_ENDPOINT = "https://stage-uploads.wepay.com";

WePay.PRODUCTION_IFRAME_ENDPOINT = "https://iframe.wepay.com";
WePay.STAGE_IFRAME_ENDPOINT= "https://stage-iframe.wepay.com";

WePay.configure = WePay.configure || function (environment, app_id, api_version, session_token) {
    var configValidation = WePay._internal.validateSDKConfiguration(environment, app_id, api_version);
    if (configValidation.hasError()) {
        return configValidation.buildInvalidParams();
    }
    WePay._internal.setEnvironment(environment);
    WePay._internal.setAppID(app_id);
    WePay._internal.setAPIVersion(api_version);
    WePay._internal.setSessionToken(session_token);
    WePay._internal.generate_risk_token_delayed();
};

/*
 * IE compatibility section
 * to support Promises in IE, conditionally load the Bluebird library
 */
var isIe11 = !!window.MSInputMethodContext && !!document.documentMode;
if (isIe11) {
    var script = document.createElement('script');
    script.src = "https://cdn.wepay.com/bluebird.min.js";
    document.head.appendChild(script);
}

/**
 * creates a new credit card iframe element on window document, and returns a new instance creditCardIframe object.
 * creditCardIframe object has tokenize public method which can perform token submission when it is invoked.
 *
 * @param {string} iframe_container_id value used by JavaScript to append the credit card iframe element on the window document.
 * @param {object} options is an object but only takes custom_style, show_labels, show_error_messages, show_error_messages_when_unfocused, use_one_liner, show_placeholders, show_error_icon, show_required_asterisk, resize_on_error_message, custom_required_field_error_message properties
 * @property {object && optional} options.custom_style is used to overwrite default css style of the input elements of the iframe, must be structured according to specifications (https://prerelease-developer.wepay.com/docs/basic-integration/process-payments).
 * @return {object} The new creditCardIframe object.
 */

WePay.createCreditCardIframe = WePay.createCreditCardIframe || function (iframe_container_id, options) {

    // default options should be empty object
    options = options || {};

    // validate SDK configuration of WePay library
    var configValidation = WePay._internal.validateSDKIsConfigured();

    if (configValidation.hasError()) {
        return configValidation.buildSDKConfigurationError();
    }

    var error = new WePay._internal.errorCollection();

    if (typeof iframe_container_id !== "string") {
        error.addWrongStringTypeError("iframe_container_id", iframe_container_id);
    }

    var element = document.getElementById(iframe_container_id);
    if (!element) {
        error.addIDNotFoundError("iframe_container_id", iframe_container_id);
    }

    if (typeof options !== "object") {
        error.addWrongObjectTypeError("options", options);
    } else {
        // check if options' properties are allowed, otherwise print warning in console
        for (var property in options) {
            if (options.hasOwnProperty(property)) {
                WePay._internal.allowOptions(property, ['custom_style', 'show_labels', 'show_placeholders', 'show_error_messages', 'show_error_messages_when_unfocused', 'use_one_liner', 'show_error_icon', 'show_required_asterisk', 'resize_on_error_message', 'custom_required_field_error_message']);
            }
        }
    }

    if (error.hasError()) {
        return error.buildInvalidParams();
    }

    var initial_height = '76px';

    //Â Why doÂ weÂ calculateÂ theÂ defaultÂ creditÂ cardÂ iframeÂ heightÂ baseÂ onÂ theÂ inputÂ elements?Â Â Â Â Â Â Â Â Â Â Â Â Â 
    //Â When a partnerÂ usesÂ theÂ heightÂ propertyÂ inÂ custom_styleÂ baseÂ object, eachÂ ofÂ theÂ inputÂ elementÂ willÂ applyÂ theÂ pxÂ Â Â Â Â Â Â Â Â Â Â Â Â Â Â Â 
    //Â TheÂ entireÂ contentÂ ofÂ ccÂ iframeÂ willÂ changeÂ baseÂ onÂ thatÂ whenÂ theÂ pageÂ load. Wepay.jsÂ listens to theÂ Â Â Â Â Â Â Â Â Â Â Â Â Â Â Â 
    //Â resizeÂ postMessageÂ eventÂ fromÂ ccÂ iframeÂ toÂ updateÂ theÂ finalÂ entireÂ iframeÂ contentÂ height.Â However,Â it'sÂ notÂ goodÂ enough.
    //Â UserÂ willÂ seeÂ aÂ transitionÂ fromÂ defaultÂ heightÂ toÂ finalÂ entireÂ iframeÂ contentÂ height.Â SoÂ thisÂ try/catchÂ blockÂ isÂ toÂ pre-calculateÂ Â Â Â Â Â Â Â Â Â Â Â Â Â Â Â 
    //Â theÂ entireÂ ccÂ iframeÂ contentÂ height.Â ItÂ provides a betterÂ userÂ experience.
    if (WePay._internal.checkNested(options, 'custom_style', 'styles', 'base', 'height')) {
        var customHeight = options['custom_style']['styles']['base']['height'];
        if (customHeight) {
            var height = customHeight.split('px')[0];
            if (!isNaN(height)) {
                initial_height = height * 2 + 10 + 'px';
            }
        }
    }

    // create an instance of a credit card tokenization iframe object
    var credit_card_iframe_instance = new WePay._internal.iframe();

    credit_card_iframe_instance.init(
        iframe_container_id,
        options,
        {
            iframe_route: '/paymentMethods/creditCard/v3',
            initial_height: initial_height,
            sandbox_enabled: true,
            sandbox_attributes: "allow-forms allow-scripts allow-same-origin"
        }
    );

    delete credit_card_iframe_instance.test;

    return credit_card_iframe_instance;
};

/**
 * @param {string} iframe_container_id value can be used JavaScript to append credit card iframe element on window document.
 *
 * @param {Object} configs
    * @property {Function && required} configs.on_success is a callback to handle the transaction data returned by google pay
    * @property {Function && required} configs.on_error is a callback to handle error responses from google pay
    * @property {Object && required} configs.button_configs is an object that takes only certain properties
        * @property {Object && required} configs.button_configs.paymentRequest is an object containing transaction information along with additional settings
            * @property {Object && required} configs.button_configs.paymentRequest.transactionInfo is an object containing the total price of the transaction. Required by the Google Pay Button
                * @property {string && required} configs.button_configs.paymentRequest.transactionInfo.totalPrice the total price of the transaction (e.g. '4.99')
                * @property {string && required} configs.button_configs.paymentRequest.transactionInfo.currencyCode e.g. 'USD'
                * @property {string && required} configs.button_configs.paymentRequest.transactionInfo.countryCode e.g. 'US'
                * @property {string} configs.button_configs.paymentRequest.transactionInfo.totalPriceStatus "NOT_CURRENTLY_KNOWN" | "ESTIMATED" | "FINAL"
                * @property {string} configs.button_configs.paymentRequest.transactionInfo.totalPriceLabel label to show next to the total price
                * @property {string} configs.button_configs.paymentRequest.transactionInfo.checkoutOption "DEFAULT" | "COMPLETE_IMMEDIATE_PURCHASE"
                * @property {Object[]} configs.button_configs.paymentRequest.transactionInfo.displayItems a list of line items to display as a summary for the purchase price
                    * @property {string} configs.button_configs.paymentRequest.transactionInfo.displayItems.label a string for the display item label
                    * @property {string} configs.button_configs.paymentRequest.transactionInfo.displayItems.type "LINE_ITEM" | "SUBTOTAL" | "TAX" | "DISCOUNT" | "SHIPPING_OPTION"
                    * @property {string} configs.button_configs.paymentRequest.transactionInfo.displayItems.price a string showing the total amount for the display item
                    * @property {string} configs.button_configs.paymentRequest.transactionInfo.displayItems.status "FINAL" | "PENDING"
            * @property {boolean} configs.button_configs.paymentRequest.emailRequired whether or not to collect the payer's email address
            * @property {boolean} configs.button_configs.paymentRequest.shippingAddressRequired whether or not to collect the payer's shipping address
            * @property {Object} configs.button_configs.paymentRequest.shippingAddressParameters options if shipping address is required
                * @property {string[]} configs.button_configs.paymentRequest.shippingAddressParameters.allowedCountryCodes list of allowed country codes
                * @property {boolean} configs.button_configs.paymentRequest.shippingAddressParameters.phoneNumberRequired whether or not to collect the payer's phone number
            * @property {Object} configs.button_configs.paymentRequest.allowedPaymentMethods payment method options
                * @property {string[]} configs.button_configs.paymentRequest.allowedPaymentMethods.allowedCardNetworks 'MASTERCARD', 'VISA', 'AMEX', 'DISCOVER', 'JCB'
                * @property {boolean} configs.button_configs.paymentRequest.allowedPaymentMethods.billingAddressRequired whether or not to collect the payer's billing address
            * @property {Object} configs.button_configs.paymentRequest.offerInfo offer options
                * @property {Object[]} configs.button_configs.paymentRequest.offerInfo.offers a list of offers to display to the payer - an offer includes { redemptionCode: string, description: string }
            * @property {boolean} configs.button_configs.paymentRequest.shippingOptionRequired whether or not to collect the payer's shipping option
            * @property {Object} configs.button_configs.paymentRequest.shippingOptionParameters shipping options
                * @property {Object[]} configs.button_configs.paymentRequest.shippingOptionParameters.shippingOptions a list of shipping options the payer can select - { id: string, label: string, description: string }
                * @property {string} configs.button_configs.paymentRequest.shippingOptionParameters.defaultSelectedOptionId the id of the default shipping method
        * @property {string} configs.button_configs.buttonColor "default" | "black" | "white"
        * @property {string} configs.button_configs.buttonType "book" | "buy" | "checkout" | "donate" | "order" | "pay" | "plain" | "subscribe" | "long" | "short"
        * @property {string} configs.button_configs.buttonLocale "en" | "ar" | "bg" | "ca" | "cs" | "da" | "de" | "el" | "es" | "et" | "fi" | "fr" | "hr" | "id" | "it" | "ja" | "ko" | "ms" | "nl" | "no" | "pl" | "pt" | "ru" | "sk" | "sl" | "sr" | "sv" | "th" | "tr" | "uk" | "zh"
        * @property {string} configs.button_configs.buttonSizeMode "static" | "fill"
        * @property {string} configs.button_configs.className class name to attach to the button
        * @property {Object} configs.button_configs.style object of CSS properties
 */
WePay.createGooglePayIframe = WePay.createGooglePayIframe || function (iframe_container_id, configs) {

    // default iframeConfigs should be empty object
    var iframeConfigs = configs || {};

    // validate SDK configuration of WePay library
    var configValidation = WePay._internal.validateSDKIsConfigured();

    if (configValidation.hasError()) {
        return configValidation.buildSDKConfigurationError();
    }

    var error = new WePay._internal.errorCollection();

    if (typeof iframe_container_id !== "string") {
        error.addWrongStringTypeError("iframe_container_id", iframe_container_id);
    }

    var element = document.getElementById(iframe_container_id);
    if (!element) {
        error.addIDNotFoundError("iframe_container_id", iframe_container_id);
    }

    if (typeof iframeConfigs !== "object") {
        error.addWrongObjectTypeError("iframeConfigs", iframeConfigs);
    } else {
        // check if iframeConfigs' properties are allowed, otherwise print warning in console
        for (var property in iframeConfigs) {
            if (iframeConfigs.hasOwnProperty(property)) {
                WePay._internal.allowOptions(property, ['button_configs', 'on_success', 'on_error', 'on_update_payment_data']);
            }
        }
    }

    if (!iframeConfigs.button_configs) {
        error.addParamIsMissing("button_configs");
    } else if (typeof iframeConfigs.button_configs !== "object") {
        error.addWrongObjectTypeError("button_configs", iframeConfigs.button_configs);
    } else if (!iframeConfigs.button_configs.paymentRequest) {
        error.addParamIsMissing("button_configs.paymentRequest");
    } else if (typeof iframeConfigs.button_configs.paymentRequest !== "object") {
        error.addWrongObjectTypeError("button_configs.paymentRequest", iframeConfigs.button_configs.paymentRequest);
    } else if (!iframeConfigs.button_configs.paymentRequest.transactionInfo) {
        error.addParamIsMissing("button_configs.paymentRequest.transactionInfo");
    } else if (typeof iframeConfigs.button_configs.paymentRequest.transactionInfo !== "object") {
        error.addWrongObjectTypeError("button_configs.paymentRequest.transactionInfo", iframeConfigs.button_configs.paymentRequest.transactionInfo);
    } else {
        var requiredTransactionInfoParams = ["currencyCode", "countryCode", "totalPrice"];

        for (var i = 0; i < requiredTransactionInfoParams.length; i++) {
            var param = requiredTransactionInfoParams[i];
            if (!(param in iframeConfigs.button_configs.paymentRequest.transactionInfo)) {
                error.addParamIsMissing("button_configs.paymentRequest.transactionInfo." + param);
            }
        }
    }


    if (error.hasError()) {
        return error.buildInvalidParams();
    }

    var initial_height = "100%";

    // create an instance of a credit card tokenization iframe object
    var google_pay_iframe_instance = new WePay._internal.wallet_iframe('Google Pay');

    google_pay_iframe_instance.init(
        iframe_container_id,
        iframeConfigs,
        {
            iframe_route: '/paymentMethods/googlePay',
            initial_height: initial_height,
            sandbox_enabled: true,
            sandbox_attributes: "allow-forms allow-scripts allow-same-origin allow-popups",
        }
    );

    delete google_pay_iframe_instance.test;

    return google_pay_iframe_instance;
};

WePay.createApplePayIframe = WePay.createApplePayIframe || function (iframe_container_id, configs) {

    // default iframeConfigs should be empty object
    var iframeConfigs = configs || {};

    // validate SDK configuration of WePay library
    var configValidation = WePay._internal.validateSDKIsConfigured();

    if (configValidation.hasError()) {
        return configValidation.buildSDKConfigurationError();
    }

    var error = new WePay._internal.errorCollection();

    if (typeof iframe_container_id !== "string") {
        error.addWrongStringTypeError("iframe_container_id", iframe_container_id);
    }

    var element = document.getElementById(iframe_container_id);
    if (!element) {
        error.addIDNotFoundError("iframe_container_id", iframe_container_id);
    }

    if (typeof iframeConfigs !== "object") {
        error.addWrongObjectTypeError("iframeConfigs", iframeConfigs);
    } else {
        // check if iframeConfigs' properties are allowed, otherwise print warning in console
        for (var property in iframeConfigs) {
            if (iframeConfigs.hasOwnProperty(property)) {
                WePay._internal.allowOptions(property, ['button_configs', 'on_success', 'on_error', 'on_update_payment_data']);
            }
        }
    }

    if (!iframeConfigs.button_configs) {
        error.addParamIsMissing("button_configs");
    } else if (typeof iframeConfigs.button_configs !== "object") {
        error.addWrongObjectTypeError("button_configs", iframeConfigs.button_configs);
    } else if (!iframeConfigs.button_configs.accountId) {
        error.addParamIsMissing("button_configs.accountId");
    } else if (typeof iframeConfigs.button_configs.accountId !== "string") {
        error.addWrongStringTypeError("button_configs.accountId", iframeConfigs.button_configs.accountId);
    } else if (!iframeConfigs.button_configs.paymentRequest) {
        error.addParamIsMissing("button_configs.paymentRequest");
    } else if (typeof iframeConfigs.button_configs.paymentRequest !== "object") {
        error.addWrongObjectTypeError("button_configs.paymentRequest", iframeConfigs.button_configs.paymentRequest);
    } else if (!iframeConfigs.button_configs.paymentRequest.transactionInfo) {
        error.addParamIsMissing("button_configs.paymentRequest.transactionInfo");
    } else if (typeof iframeConfigs.button_configs.paymentRequest.transactionInfo !== "object") {
        error.addWrongObjectTypeError("button_configs.paymentRequest.transactionInfo", iframeConfigs.button_configs.paymentRequest.transactionInfo);
    } else {
        var requiredTransactionInfoParams = ["currencyCode", "countryCode", "totalPrice"];

        for (var i = 0; i < requiredTransactionInfoParams.length; i++) {
            var param = requiredTransactionInfoParams[i];
            if (!(param in iframeConfigs.button_configs.paymentRequest.transactionInfo)) {
                error.addParamIsMissing("button_configs.paymentRequest.transactionInfo." + param);
            }
        }
    }


    if (error.hasError()) {
        return error.buildInvalidParams();
    }

    var initial_height = "100%";

    // create an instance of a credit card tokenization iframe object
    var apple_pay_iframe_instance = new WePay._internal.wallet_iframe('Apple Pay');

    apple_pay_iframe_instance.init(
        iframe_container_id,
        iframeConfigs,
        {
            iframe_route: '/paymentMethods/applePay',
            initial_height: initial_height,
            sandbox_enabled: true,
            sandbox_attributes: "allow-forms allow-scripts allow-same-origin allow-popups",
        }
    );

    delete apple_pay_iframe_instance.test;

    return apple_pay_iframe_instance;
};

/**
 * creates a new kyc iframe element on window document, and returns a new instance kyc iframe object.
 * this object has a tokenize public method that can perform token submission when it is invoked.
 *
 * @param {string} iframe_container_id value used by JavaScript to append the kyc iframe element on the window document.
 * @param {object} options contains the additional options to create and set up the iframe.
 * @property {string && required} options.country_code is used to specify which country-specific version of the iframe to load.
 * @property {boolean && optional} options.ssn4_enabled can be set to false to force the UI to collect SSN9 at all times from all entities. By default, SSN4 is collected from entity types supporting SSN4.
 * @return {iframe} The new kyc iframe object.
 */
WePay.createKycIframe = WePay.createKycIframe || function (iframe_container_id, options) {
    // validate SDK configuration of WePay library
    var configValidation = WePay._internal.validateSDKIsConfigured();

    // set valid country_code list and country list helper function (for IE compatibility)
    var validCountriesList = ["US", "CA"];

    if (configValidation.hasError()) {
        return configValidation.buildSDKConfigurationError();
    }

    var error = new WePay._internal.errorCollection();

    if (typeof iframe_container_id !== "string") {
        error.addWrongStringTypeError("iframe_container_id", iframe_container_id);
    }

    var element = document.getElementById(iframe_container_id);
    if (!element) {
        error.addIDNotFoundError("iframe_container_id", iframe_container_id);
    }

    if (!options) {
        error.addParamIsMissing("options");
    } else if (typeof options !== "object") {
        error.addWrongObjectTypeError("options", options);
    } else {
        if (!options.country_code) {
            error.addParamIsMissing("options.country_code");
        } else if (typeof options.country_code !== "string") {
            error.addWrongStringTypeError("options.country_code", options.country_code);
        } else if (validCountriesList.indexOf(options.country_code) == -1) {
            error.addParamValueIsInvalidEnumError("options.country_code", validCountriesList);
        } else {
            // check if options' properties are allowed, otherwise print warning in console
            for (var property in options) {
                if (options.hasOwnProperty(property)) {
                    WePay._internal.allowOptions(property, ['custom_style', 'country_code', 'ssn4_enabled']);
                }
            }
        }
    }

    if (error.hasError()) {
        return error.buildInvalidParams();
    }

    // create an instance of a kyc card tokenization iframe object
    var kyc_iframe_instance = new WePay._internal.iframe();

    // initialize the kyc iframe with an options object and the speific internal settings
    kyc_iframe_instance.init(
        iframe_container_id,
        options,
        {
            iframe_route: '/kyc/web_view/v3',
            initial_height: '800px'
        }
    );

    delete kyc_iframe_instance.test;

    return kyc_iframe_instance;
};

/**
 * creates a new payout iframe element on window document, and returns a new instance payout object.
 * payoutIframe object has tokenize public method which can perform token submission when it is invoked.
 *
 * @param {string} iframe_container_id value used by JavaScript to append the payout iframe element on the window document.
 * @param {object} options is an object but only takes custom_style property
 * @return {object} The new payoutIframe object.
 */

WePay.createPayoutIframe = WePay.createPayoutIframe || function (iframe_container_id, options) {
    // validate SDK configuration of WePay library
    var configValidation = WePay._internal.validateSDKIsConfigured();

    if (configValidation.hasError()) {
        return configValidation.buildSDKConfigurationError();
    }

    var error = new WePay._internal.errorCollection();

    if (typeof iframe_container_id !== "string") {
        error.addWrongStringTypeError("iframe_container_id", iframe_container_id);
    }

    var element = document.getElementById(iframe_container_id);
    if (!element) {
        error.addIDNotFoundError("iframe_container_id", iframe_container_id);
    }
    if (!options) {
        error.addParamIsMissing("options");
    } else if (options !== Object(options)) {
        error.addWrongObjectTypeError("options", options);
    } else {
        if (!options.country_code) {
            error.addParamIsMissing("options.country_code");
        } else if (typeof options.country_code !== "string") {
            error.addWrongStringTypeError("options.country_code", options.country_code);
        } else if (options.country_code !== "US" && options.country_code != "CA") {
            error.addParamValueIsInvalidEnumError("options.country_code", ["US", "CA"]);
        }
        else {
            // check if options' properties are allow, otherwise print warning in console
            for (var property in options) {
                if (options.hasOwnProperty(property)) {
                    WePay._internal.allowOptions(property, ["country_code", "custom_style"]);
                }
            }
        }
    }

    if (error.hasError()) {
        return error.buildInvalidParams();
    }

    // create an instance of a payout tokenization iframe object
    var payout_iframe_instance = new WePay._internal.iframe();
    var internal_settings = {
        initial_height: '500px',
        iframe_route: '/payouts/addBank/tokenize'
    };

    payout_iframe_instance.init(
        iframe_container_id,
        options,
        internal_settings
    );

    delete payout_iframe_instance.test;

    return payout_iframe_instance;
};

WePay.tokens = WePay.tokens || {};

WePay.tokens.create = WePay.tokens.create || function (body, headers, callback) {
    return WePay._internal.makeJSONRequest("general", "/tokens", "POST", body, headers, callback);
};

WePay.documents = WePay.documents || {};

WePay.documents.create = WePay.documents.create || function (body, headers, callback) {
    return WePay._internal.makeFormRequest("upload", "/documents", "POST", body, headers, callback);
};

WePay.tags = WePay.tags || {
    'device_token': 0,
    'uuid': function () {
        // http://stackoverflow.com/a/873856
        // http://www.ietf.org/rfc/rfc4122.txt
        var s = [];
        var hexDigits = "0123456789abcdef";
        for (var i = 0; i < 36; i++) {
            s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
        }
        s[14] = "4";  // bits 12-15 of the time_hi_and_version field to 0010
        s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1);  // bits 6-7 of the clock_seq_hi_and_reserved to 01
        s[8] = s[13] = s[18] = s[23] = "-"; //dashes are in the spec
        return s.join("");
    },
    'generate': function (session_id) {
        var div = document.createElement("div");
        var div_div = document.createElement("div");
        var div_img = document.createElement("img");
        var div_script = document.createElement("script");
        var div_object = document.createElement("object");
        var div_object_param = document.createElement("param");
        var div_object_div = document.createElement("div");

        div.id = "WePay-tags";
        div.style.position = "absolute";
        div.style.left = "-1000px";
        div.style.maxHeight = "0px";
        div.style.overflow = "hidden";

        div_div.style.background = "url('https://t.wepay.com/fp/clear.png?org_id=ncwzrc4k&session_id=" + session_id + "&m=1')";

        div_img.src = "https://t.wepay.com/fp/clear.png?org_id=ncwzrc4k&session_id=" + session_id + "&m=2";
        div_img.alt = "";

        div_script.src = "https://t.wepay.com/fp/check.js?org_id=ncwzrc4k&session_id=" + session_id;
        div_script.type = "text/javascript";
        div_script.async = "true";

        div_object.type = "application/x-shockwave-flash";
        div_object.data = "https://t.wepay.com/fp/fp.swf?org_id=ncwzrc4k&session_id=" + session_id;
        div_object.width = 1;
        div_object.height = 1;
        div_object.id = "obj_id";
        div_object_param.name = "movie";
        div_object_param.value = "https://t.wepay.com/fp/fp.swf?org_id=ncwzrc4k&session_id=" + session_id;
        div_object.appendChild(div_object_param);
        div_object.appendChild(div_object_div);

        div.appendChild(div_div);
        div.appendChild(div_img);
        div.appendChild(div_script);
        div.appendChild(div_object);

        return div;
    },
    'enable_device': function (session_id) {
        this.device_token = session_id;
    },
    'insert': function (session_id) {
        // Give preference to hardcoded, then generated cached, then generate as a last resort
        session_id = session_id || this.device_token || WePay.tags.uuid();
        if (!document.getElementById('WePay-tags')) {
            document.body.appendChild(WePay.tags.generate(session_id));
        }
        return session_id;
    }
};

/**
 * Bank account
 */

WePay.createPaymentBankLightBox = WePay.createPaymentBankLightBox || function (internalEventsListener, options) {
    // default options should be empty object
    options = options || {};

    // validate SDK configuration of WePay library
    var configValidation = WePay._internal.validateSDKIsConfigured();

    if (configValidation.hasError()) {
        return configValidation.buildSDKConfigurationError();
    }

    var error = new WePay._internal.errorCollection();

    if (typeof options !== "object") {
        error.addWrongObjectTypeError("options", options);
    } else {
        // check if options' properties are allowed, otherwise print warning in console
        for (var property in options) {
            if (options.hasOwnProperty(property)) {
                WePay._internal.allowOptions(property, ['avoid_micro_deposits']);
            }
        }
    }

    if (error.hasError()) {
        return error.buildInvalidParams();
    }

    var payment_bank_light_box = new WePay._internal.light_box();

    payment_bank_light_box.init(
        WePay._internal.plaidEventHandler,
        internalEventsListener,
        options,
        {
            iframe_route: '/paymentMethods/bankAccount',
            sandbox_enabled: true,
            sandbox_attributes: "allow-forms allow-scripts allow-same-origin"
        }
    );
    delete payment_bank_light_box.test;
    return payment_bank_light_box;
};



///// Internal functionality ///////
WePay._internal = WePay._internal || new (function () {
    // This will be shared by all _internal objects, but we will only ever create one.
    var _data = {};
    var that = this;

    this.setAppID = function (app_id) {
        _data.app_id = app_id;
    };

    this.setAPIVersion = function (api_version) {
        _data.api_version = api_version;
    };

    this.setSessionToken = function (session_token) {
        _data.session_token = session_token;
    };

    this.endpointsMapper = function (endpoint) {
        var dic = {
            "general": _data.endpoint,
            "upload": _data.upload_endpoint,
            "iframe": _data.iframe_endpoint
        };
        return dic[endpoint];
    };

    this.setEnvironment = function (environment) {
        _data.environment = environment;
        switch (environment) {
            case "stage":
                _data.endpoint = WePay.STAGING_ENDPOINT;
                _data.upload_endpoint = WePay.STAGING_UPLOAD_ENDPOINT;
                _data.iframe_endpoint = WePay.STAGE_IFRAME_ENDPOINT;
                break;
            case "production":
                _data.endpoint = WePay.PRODUCTION_ENDPOINT;
                _data.upload_endpoint = WePay.PRODUCTION_UPLOAD_ENDPOINT;
                _data.iframe_endpoint = WePay.PRODUCTION_IFRAME_ENDPOINT;
                break;
        }
    };

    this.getAppID = function () {
        return _data.app_id;
    };

    this.getAPIVersion = function () {
        return _data.api_version;
    };

    this.getSessionToken = function () {
        return _data.session_token;
    };

    /**
     * Validates the current SDK configuration. Returns an errorCollection object. The return result's
     * hasError() function will return true if there is an error.
     *
     * @return {errorCollection} The error collection, hasError() will be false if configuration is valid.
     */
    this.validateSDKIsConfigured = function () {
        var configValidation = this.validateSDKConfiguration(
            _data.environment,
            _data.app_id,
            _data.api_version);

        return configValidation;
    };

    this.plaidErrorHandler = function (parsedJson) {
        // Handle responses from Plaid
        var plaidError = {
            INVALID_CREDENTIALS: "INVALID_CREDENTIALS",
            INVALID_MFA: "INVALID_MFA",
            ITEM_LOCKED: "ITEM_LOCKED",
            ITEM_NOT_SUPPORTED: "ITEM_NOT_SUPPORTED",
            USER_SETUP_REQUIRED: "USER_SETUP_REQUIRED",
            NO_ACCOUNTS: "NO_ACCOUNTS",
            NO_AUTH_ACCOUNTS: "NO_AUTH_ACCOUNTS",
            INSTITUTION_NOT_RESPONDING: "INSTITUTION_NOT_RESPONDING"
        };

        // default error response
        var errorResponse = {
            "error_code": "UNEXPECTED_ERROR",
            "error_message": "There was an unknown error.",
        };

        var plaidErrorMapping = {};

        plaidErrorMapping[plaidError.INVALID_CREDENTIALS] = {
            "error_code": "COULD_NOT_AUTHENTICATE",
            "error_message": "The supplied credentials do not have permission to perform this action.",
            "details": [
                {
                    "reason_code": "INVALID_CREDENTIALS",
                    "message": "The credentials provided are invalid. Please try again."
                }
            ]
        };

        plaidErrorMapping[plaidError.INVALID_MFA] = {
            "error_code": "COULD_NOT_AUTHENTICATE",
            "error_message": "The supplied credentials do not have permission to perform this action.",
            "details": [
                {
                    "reason_code": "INVALID_MFA",
                    "message": "MFA responses provided are invalid. Please try again."
                }
            ]
        };

        plaidErrorMapping[plaidError.ITEM_LOCKED] = {
            "error_code": "PAYMENT_METHODS_COULD_NOT_BE_RETRIEVED",
            "error_message": "The payment methods could not be retrieved.",
            "details": [
                {
                    "reason_code": "REJECTED_BY_ISSUING_BANK",
                    "message": "User account locked. Contact the financial institution to unlock."
                }
            ]
        };

        plaidErrorMapping[plaidError.ITEM_NOT_SUPPORTED] = {
            "error_code": "PAYMENT_METHODS_COULD_NOT_BE_RETRIEVED",
            "error_message": "The payment methods could not be retrieved.",
            "details": [
                {
                    "reason_code": "REJECTED_BY_ISSUING_BANK",
                    "message": "This account is restricted by the financial institution selected. " +
                        "User Account canâ€™t be supported by Plaid."
                }
            ]
        };

        plaidErrorMapping[plaidError.USER_SETUP_REQUIRED] = {
            "error_code": "PAYMENT_METHODS_COULD_NOT_BE_RETRIEVED",
            "error_message": "The payment methods could not be retrieved.",
            "details": [
                {
                    "reason_code": "USER_SETUP_REQUIRED",
                    "message": "Pending action with the financial institution. " +
                        "Please log in directly to resolve and then retry."
                }
            ]
        };

        plaidErrorMapping[plaidError.NO_ACCOUNTS] = {
            "error_code": "PAYMENT_METHODS_COULD_NOT_BE_RETRIEVED",
            "error_message": "The payment methods could not be retrieved.",
            "details": [
                {
                    "reason_code": "NO_ACCOUNT_FOUND",
                    "message": "No matching accounts found."
                }
            ]
        };

        plaidErrorMapping[plaidError.NO_AUTH_ACCOUNTS] = {
            "error_code": "PAYMENT_METHODS_COULD_NOT_BE_RETRIEVED",
            "error_message": "The payment methods could not be retrieved.",
            "details": [
                {
                    "reason_code": "NO_AUTH_ACCOUNT_FOUND",
                    "message": "No valid checking or savings accounts found to retrieve routing numbers."
                }
            ]
        };

        plaidErrorMapping[plaidError.INSTITUTION_NOT_RESPONDING] = {
            "error_code": "PAYMENT_METHODS_COULD_NOT_BE_RETRIEVED",
            "error_message": "The payment methods could not be retrieved.",
            "details": [
                {
                    "reason_code": "INSTITUTE_NOT_RESPONDING",
                    "message": "No response from the financial institution. Please try again later."
                }
            ]
        };

        if (parsedJson["errorCode"] in plaidErrorMapping) {
            // If errorCode is in the mapper, update the fields.
            var mappedError = plaidErrorMapping[parsedJson["errorCode"]];
            errorResponse["error_code"] = mappedError["error_code"];
            errorResponse["error_message"] = mappedError["error_message"];
            errorResponse["details"] = JSON.parse(JSON.stringify(mappedError["details"]));
        }
        return errorResponse;
    };

    this.microDepositsErrorHandler = function (parsedJson) {
        // Handle errors from the micro deposits flow
        var microDepositsError = {
            INVALID_EMAIL_TYPE: "INVALID_EMAIL_TYPE",
            INVALID_ACCOUNT_NUMBER: "INVALID_ACCOUNT_NUMBER",
            INVALID_ROUTING_NUMBER: "INVALID_ROUTING_NUMBER",
        };

        // default error response
        var errorResponse = {
            "error_code": "UNEXPECTED_ERROR",
            "error_message": "There was an unknown error.",
        };
        var microDepositsErrorMapping = {};

        microDepositsErrorMapping[microDepositsError.INVALID_EMAIL_TYPE] = {
            "error_code": "INVALID_PARAMS",
            "error_message": "Invalid parameter(s).",
            "details": [
                {
                    "reason_code": "PARAM_VALUE_IS_INVALID_PATTERN",
                    "message": "Expected a value matching the regular expression " +
                        "'^([^,:;=@\"'\\\\\\s()\\[\\]]+)+@([a-zA-Z0-9-]+\\.)+[a-zA-Z]{2,}$'."
                }
            ]
        };
        microDepositsErrorMapping[microDepositsError.INVALID_ACCOUNT_NUMBER] = {
            "error_code": "INVALID_PARAMS",
            "error_message": "Invalid parameter(s).",
            "details": [
                {
                    "reason_code": "PARAM_VALUE_IS_INVALID_PATTERN",
                    "message": "Expected a value matching the regular expression '^[0-9]{3,17}$'."
                }
            ]
        };
        microDepositsErrorMapping[microDepositsError.INVALID_ROUTING_NUMBER] = {
            "error_code": "INVALID_PARAMS",
            "error_message": "Invalid parameter(s).",
            "details": [
                {
                    "reason_code": "PARAM_VALUE_IS_INVALID_PATTERN",
                    "message": "Expected a value matching the regular expression '^[0-9]{9}$'."
                }
            ]
        };

        if (parsedJson["errorCode"] in microDepositsErrorMapping) {
            // If errorCode is in the mapper, update the fields.
            var mappedError = microDepositsErrorMapping[parsedJson["errorCode"]];
            errorResponse["error_code"] = mappedError["error_code"];
            errorResponse["error_message"] = mappedError["error_message"];
            errorResponse["details"] = JSON.parse(JSON.stringify(mappedError["details"]));
        }
        return errorResponse;
    };

    this.plaidEventHandler = this.plaidEventHandler || function (event) {
        var parsedJson;
        try {
            parsedJson = (event.data && typeof event.data === "string") ? JSON.parse(event.data) : event.data;
        } catch (err) {
            console.log("Could not parse JSON");
            return;
        }

        var isPlaidErrorMessage = "errorCode" in parsedJson;
        var popupClosing = parsedJson['popup_closing'];

        var response = {
            errorOccurred: undefined,
            popupClosing: popupClosing,
            response: undefined
        };

        if (isPlaidErrorMessage) {
            response.errorOccurred = true;
            // Handle bank account errors
            if (parsedJson["flow"] === "happyPath") {
                // Plaid errors
                response.response = WePay._internal.plaidErrorHandler(parsedJson);
            } else if (parsedJson["flow"] === "microdeposits") {
                // WePay micro deposits errors, mapping is still not known from product.
                response.response = WePay._internal.microDepositsErrorHandler(parsedJson);
            } else {
                // Default error response
                response.response = {
                    "error_code": "UNEXPECTED_ERROR",
                    "error_description": "There was an unknown error.",
                };
            }
        }

        if (popupClosing) {
            if ('error' in parsedJson && parsedJson['error'] === 'window_closed') {
                response.errorOccurred = true;
                response.response = {
                    "error_code": "TOKEN_CANNOT_BE_CREATED",
                    "error_message": "Token cannot be created.",
                    "details": [
                        {
                            "reason_code": "USER_CANCELED",
                            "message": "The user has canceled the action."
                        }
                    ]
                };
            }
            else {
                // pop is closing and there was no error mean successful creation of the token
                response.errorOccurred = false;
                // remove internal property from the response
                delete parsedJson.popup_closing;
                response.response = parsedJson;
            }
        }

        return response;
    };

    // shared function to initiate a XMLHttpRequest
    this.makeBaseRequest = function (endpoint, path, method, isAsync, callback) {
        var isAsync = callback !== undefined && callback !== null;
        var xhttp = new XMLHttpRequest();
        if (isAsync) {
            xhttp.onreadystatechange = function () {
                if (this.readyState == 4) {
                    callback(JSON.parse(this.responseText));
                }
            };
        }
        xhttp.open(method, this.endpointsMapper(endpoint) + path, isAsync);
        xhttp.setRequestHeader("App-Id", _data.app_id);
        xhttp.setRequestHeader("Api-Version", _data.api_version);
        if (this.getSessionToken()) {
            xhttp.setRequestHeader("Session-Token", _data.session_token);
        }
        if (this.get_risk_token()) {
            xhttp.setRequestHeader("WePay-Risk-Token", this.get_risk_token());
        }
        return xhttp;
    };

    this.makeJSONRequest = function (endpoint, path, method, data, headers, callback) {
        var isAsync = callback !== undefined && callback !== null;
        var configValidation = this.validateSDKIsConfigured();
        if (configValidation.hasError()) {
            if (callback) {
                callback(configValidation.buildSDKConfigurationError());
                return;
            } else {
                return configValidation.buildSDKConfigurationError();
            }
        }

        var xhttp = this.makeBaseRequest(endpoint, path, method, isAsync, callback);
        if (xhttp instanceof Error) {
            return xhttp;
        }
        xhttp.setRequestHeader("Content-Type", "application/json");
        this.setXhttpHeader(xhttp, headers);
        if (method === "POST" && data) {
            xhttp.send(JSON.stringify(data));
        } else {
            xhttp.send();
        }
        if (!isAsync) {
            return JSON.parse(xhttp.responseText);
        }
    };

    this.makeFormRequest = function (endpoint, path, method, data, headers, callback) {
        var isAsync = callback !== undefined && callback !== null;
        var configValidation = this.validateSDKIsConfigured();
        if (configValidation.hasError()) {
            if (callback) {
                callback(configValidation.buildSDKConfigurationError());
                return;
            } else {
                return configValidation.buildSDKConfigurationError();
            }
        }

        var xhttp = this.makeBaseRequest(endpoint, path, method, isAsync, callback);
        this.setXhttpHeader(xhttp, headers);
        if (method === "POST" && data) {
            var formData = new FormData();
            for (var key in data) {
                formData.append(key, data[key]);
            }
            xhttp.send(formData);
        } else {
            xhttp.send();
        }
        if (!isAsync) {
            return JSON.parse(xhttp.responseText);
        }
    };

    this.setXhttpHeader = function (xhttp, headers) {
        // Don't let additional headers override protected ones
        var protected_headers = ["Content-Type", "App-Id", "App-Token", "Api-Version", "WePay-Risk-Token"];
        if (headers) {
            var headerKeys = Object.keys(headers);
            for (var a = 0; a < headerKeys.length; a++) {
                if (protected_headers.indexOf(headerKeys[a]) == -1) {
                    xhttp.setRequestHeader(headerKeys[a], headers[headerKeys[a]]);
                }
            }
        }
    };

    /**
     * Validates the SDK configuration specified by the parameters. Returns an errorCollection object. The
     * returned object's hasError() function will return true if there is an error.
     *
     * @param {string} environment the environment to use in WePay. Can be "stage" or "production"
     * @param {string} app_id the App ID in WePay system.
     * @param {string} api_version the API version to use with requests to WePay.
     * @return {errorCollection} The error collection, hasError() will be false if configuration is valid.
     */
    this.validateSDKConfiguration = function (environment, app_id, api_version) {
        var errorCollection = new WePay._internal.errorCollection();

        // Validate App ID
        if (app_id === undefined) {
            errorCollection.addParamIsMissing("app_id");
        } else if (typeof app_id !== "string") {
            errorCollection.addWrongStringTypeError("app_id", app_id);
        }

        // Validate env
        if (environment === undefined) {
            errorCollection.addParamIsMissing("environment");
        } else if (typeof environment !== "string") {
            errorCollection.addWrongStringTypeError("environment", environment);
        } else if (environment !== "stage" && environment != "production") {
            errorCollection.addParamValueIsInvalidEnumError("environment", ["stage", "production"]);
        }

        // Validate API version
        if (api_version === undefined) {
            errorCollection.addParamIsMissing("api_version");
        } else if (typeof api_version !== "string") {
            errorCollection.addWrongStringTypeError("api_version", api_version);
        }

        return errorCollection;
    };

    this.get_risk_token = function () {
        return WePay.tags.device_token;
    };

    this.generate_risk_token = function () {
        var device_id = WePay.tags.insert();
        WePay.tags.enable_device.bind(WePay.tags, device_id)();
    };

    this.generate_risk_token_delayed = function () {
        setTimeout(that.generate_risk_token, 5000);
    };

    this.checkNested = function (obj /*, level1, level2, ... levelN*/) {
        var args = Array.prototype.slice.call(arguments, 1);

        for (var i = 0; i < args.length; i++) {
            if (!obj || !obj.hasOwnProperty(args[i])) {
                return false;
            }
            obj = obj[args[i]];
        }
        return true;
    };

    this.errorCollection = function () {
        var details = [];

        var hasError = function () {
            return details.length === 0 ? false : true;
        };

        var buildInvalidParams = function () {
            return {
                "error_code": "INVALID_PARAMS",
                "error_message": "Invalid parameter(s).",
                "details": details
            };
        };

        var buildUnexpectedError = function () {
            return {
                "error_code": "UNEXPECTED_ERROR",
                "error_message": "There was an unknown error."
            };
        };

        var buildSDKConfigurationError = function () {
            return {
                "error_code": "INVALID_SDK_CONFIGURATION",
                "error_message": "The application is not configured correctly.",
                "details": details
            };
        };

        var addParamIsMissing = function (target) {
            details.push({
                "target": target,
                "target_type": 'SDK_PARAMETER',
                "reason_code": 'PARAM_IS_MISSING',
                "message": "A required parameter is missing."
            });
        };

        var addParamValueIsInvalidEnumError = function (target, expectedValues) {
            details.push({
                "target": target,
                "target_type": 'SDK_PARAMETER',
                "reason_code": 'PARAM_VALUE_IS_INVALID_ENUM',
                "message": "Expected value in [ " + expectedValues.join(", ") + " ]."
            });
        };

        var addIDNotFoundError = function (target, id) {
            details.push({
                "target": target,
                "target_type": 'SDK_PARAMETER',
                "reason_code": 'ID_NOT_FOUND',
                "message": "ID " + id + " not found."
            });
        };

        var addWrongStringTypeError = function addWrongStringTypeError(target, value) {
            details.push({
                "target": target,
                "target_type": "SDK_PARAMETER",
                "reason_code": "PARAM_VALUE_IS_WRONG_TYPE",
                "message": "Expected type in [ string ] but found type " + typeof value + "."
            });
        };

        var addWrongObjectTypeError = function (target, value) {
            details.push({
                "target": target,
                "target_type": "SDK_PARAMETER",
                "reason_code": "PARAM_VALUE_IS_WRONG_TYPE",
                "message": "Expected type in [ object ] but found type " + typeof value + "."
            });
        };

        return {
            hasError: hasError,
            buildInvalidParams: buildInvalidParams,
            buildUnexpectedError: buildUnexpectedError,
            buildSDKConfigurationError: buildSDKConfigurationError,
            addParamIsMissing: addParamIsMissing,
            addParamValueIsInvalidEnumError: addParamValueIsInvalidEnumError,
            addIDNotFoundError: addIDNotFoundError,
            addWrongStringTypeError: addWrongStringTypeError,
            addWrongObjectTypeError: addWrongObjectTypeError
        };
    };

    /**
     * Validate and print out warning in browser console when user enters property which does not allow.
     * @param {string} property has the value which needs to validate against allowPropertyInOptions.
     * @param {array} allowedProperties is a list of properties that are allowed to be passed in.
     * @return {Boolean} true: allow property. false: not allow property.
     */
    this.allowOptions = function (property, allowedProperties) {
        var allowPropertyInOptions = new Set(allowedProperties);
        if (!allowPropertyInOptions.has(property)) {
            console.warn(property + " is not a supported property.");
            return false;
        }
        return true;
    };

    this.light_box = function () {
        var props = {
            is_init: false,
            iframe_route: undefined,
            iframe: undefined,
            light_box: undefined
        };


        var init = function (event_handler, internal_events_listener, options, internal_settings) {
            // only init once
            if (!props.is_init) {
                props.is_init = true;
                props.iframe_route = internal_settings.iframe_route;
                props.event_handler = event_handler;
                props.internal_events_listener = internal_events_listener;
                props.avoid_micro_deposits = options['avoid_micro_deposits'];
            }

            var height = "height: 665px;";
            if (options.hasOwnProperty("avoid_micro_deposits") &&
                options['avoid_micro_deposits'] &&
                screen.width < 400) {
                height = "height: 550px;";
            }

            var iframeStyle =
                "position: absolute; " +
                "top: 0; " +
                "width:360px; " +
                "border: 0; " +
                "left: 50%; " +
                "margin-left: -180px; " +
                "border-radius: 8px; " +
                "margin-top: 30px;" +
                height;
            var lightBoxStyle =
                "z-index: 2000; " +
                "position: " +
                "fixed; " +
                "top: 0; " +
                "left: 0; " +
                "bottom: 0; " +
                "right: 0; " +
                "width: 100%; " +
                "height: 100%; " +
                "background-color: rgba(0, 0, 0, 0.6); " +
                "overflow: scroll;";

            _generateIframeUrl();

            var iframe = document.createElement('iframe');

            iframe.setAttribute('name', 'paymentBankIFrame');
            iframe.setAttribute('id', 'paymentBankIFrame');
            iframe.setAttribute('style', iframeStyle);
            iframe.src = props.iframe_src;

            var lightBox = document.createElement('div');
            lightBox.setAttribute('style', lightBoxStyle);
            lightBox.appendChild(iframe);
            lightBox.setAttribute('id', 'paymentBankLightBox');

            props.iframe = iframe;
            props.light_box = lightBox;
            document.body.appendChild(lightBox);
        };

        var _generateIframeUrl = function () {
            props.iframe_endpoint = WePay._internal.endpointsMapper('iframe');
            props.ref_id = WePay.tags.uuid();
            props.app_id = WePay._internal.getAppID();
            props.api_version = WePay._internal.getAPIVersion();
            var ref_id = encodeURIComponent(props.ref_id);
            var app_id = encodeURIComponent(props.app_id);
            var api_version = encodeURIComponent(props.api_version);

            props.iframe_src = props.iframe_endpoint + props.iframe_route +
                '?ref_id=' + ref_id +
                '&client_id=' + app_id +
                '&api_version=' + api_version;
            props.iframe_src += '&light_box=true';
            props.iframe_src += '&v3=true';
            if (props.avoid_micro_deposits) {
                console.log('info', 'avoid micro deposits');
                props.iframe_src += '&avoidMicrodeposits=true';
            }
        };

        var _eventListener = function (event) {
            // Ignore messages not sent from WePay iframe
            if (event.origin !== props.iframe_endpoint) {
                console.log("message is ignored, origin:%s, data:%s", event.origin, event.data);
                return;
            }

            var response = props.event_handler(event);

            if (response.popupClosing === true) {
                document.body.removeChild(props.light_box);
                // Only removed the event listener when pop-up is closed
                if (window.removeEventListener) {
                    window.removeEventListener('message', _eventListener);
                } else if (window.detachEvent) {
                    window.detachEvent('message', _eventListener);
                }
                if (response.errorOccurred === true) {
                    props.promise_map.reject(response.response);
                } else {
                    props.promise_map.resolve(response.response);
                }
            } else if (response.popupClosing === false
                && props.internal_events_listener) {
                props.internal_events_listener(response.response);
            }
        }.bind(this);

        if (window.addEventListener) {
            window.addEventListener("message", _eventListener, false);
        } else if (window.attachEvent) {
            window.attachEvent("message", _eventListener, false);
        }

        var tokenize = function () {
            return new Promise(function (resolve, reject) {
                props.promise_map = {
                    resolve: resolve,
                    reject: reject
                };
            });
        };

        var _exportPropsForUnitTesting = function () {
            var event = {
                _eventListener: _eventListener
            };
            return Object.assign(event, props);
        };

        return {
            init: init,
            tokenize: tokenize,
            test: _exportPropsForUnitTesting
        };
    };

    this.iframe = function () {

        var props = {
            is_init: false,
            iframe_id: undefined,
            promise_map: {},
            custom_style: undefined,
            show_labels: undefined,
            show_placeholders: undefined,
            show_error_messages: undefined,
            show_error_messages_when_unfocused: undefined,
            show_error_icon: undefined,
            show_required_asterisk: undefined,
            resize_on_error_message: undefined,
            custom_required_field_error_message: undefined,
            use_one_liner: undefined,
            country_code: undefined,
            promise_count: 0,
            iframe_endpoint: undefined,
            iframe_route: undefined,
            iframe_container_id: undefined,
        };

        /**
         *  Public Functions
         *
         * @param {string} iframe_container_id value can be used JavaScript to append credit card iframe element on window document.
         *
         * @param {object} options is an object that takes only certain properties
         * @property {object && optional} options.custom_style is used to overwrite default css style of the input elements of the iframe, must be structured according to specifications (https://prerelease-developer.wepay.com/docs/basic-integration/process-payments)
         * @property {string && optional} options.country_code is used to specify which country-specific version of the iframe to load.
         * @property {boolean && optional} options.ssn4_enabled can be set to false to force the UI to collect SSN9 at all times from all entities. By default, SSN4 is collected from entity types supporting SSN4.
         * @property {boolean && optional} options.show_labels can be set to true to show labels for each input in the credit card iframe UI.
         * @property {boolean && optional} options.show_placeholders can be set to false to hide placeholder text for each input in the credit card iframe UI.
         * @property {boolean && optional} options.show_error_messages can be set to true to show error messages under each invalid input in the credit card iframe UI.
         * @property {boolean && optional} options.show_error_messages_when_unfocused can be set to false to hide error messages when the user is not focused on the input.
         * @property {boolean && optional} options.show_error_icon can be set to false to hide the error icon that is displayed beside the error message text.
         * @property {boolean && optional} options.show_required_asterisk can be set to true to show a required asterisk beside the labels for each input in the credit card iframe UI.
         * @property {boolean && optional} options.resize_on_error_message can be set to true to only add space to the bottom of the iframe when the 'required field' error messages are displayed.
         * @property {boolean && optional} options.custom_required_field_error_message is used to show a custom error message in place of our default message when required fields are left blank.
         *
         * @property {boolean && optional} options.use_one_liner can be set to true to use one-line styling
         *
         * @param {object} internal_settings is an object containing internal settings specific to certain iframe types
         * @property {string && required} internal_settings.iframe_route is used to specify which url to call to load the iframe.
         * @property {string && required} internal_settings.initial_height is used to specify with which initial height to load the iframe.
         * @property {boolean && optional} internal_settings.sandbox_enabled is used to specify whether the sandbox attribute should be added to the iframe.
         * @property {string && optional} internal_settings.sandbox_attributes is used to specify the restrictions that should be removed from the sandbox.
         */
        var init = function (iframe_container_id, options, internal_settings) {

            // only init once
            if (!props.is_init) {

                props.is_init = true;
                props.iframe_container_id = iframe_container_id;
                props.iframe_id = iframe_container_id + "_iframe";
                props.custom_style = (options && options.custom_style) ? options.custom_style : null;
                props.iframe_route = internal_settings.iframe_route;
                props.country_code = (options && options.country_code) ? options.country_code : null;
                props.ssn4_enabled = (options && options.ssn4_enabled === false) ? false : true;
                props.show_labels = (options && options.show_labels === true) ? true : false;
                props.show_placeholders = (options && options.show_placeholders === false) ? false : true;
                props.show_error_messages = (options && options.show_error_messages === true) ? true : false;
                props.show_error_messages_when_unfocused = (options && options.show_error_messages_when_unfocused === false) ? false : true;
                props.show_error_icon = (options && options.show_error_icon === false) ? false : true;
                props.show_required_asterisk = (options && options.show_required_asterisk === true) ? true : false;
                props.resize_on_error_message = (options && options.resize_on_error_message === true) ? true : false;
                props.custom_required_field_error_message = (options && options.custom_required_field_error_message) ? options.custom_required_field_error_message : "";
                props.use_one_liner = (options && options.use_one_liner === true) ? true : false;
                props.sandbox_enabled = internal_settings.sandbox_enabled;
                props.sandbox_attributes = internal_settings.sandbox_attributes;

                _generateIframeUrl();

                // create an iframe element
                var iframe = document.createElement('iframe');
                iframe.id = props.iframe_id;
                iframe.title = "Credit Card Information Form";
                if (props.sandbox_enabled) {
                    iframe.sandbox = props.sandbox_attributes;
                }
                props.iframe = iframe;

                props.iframe.style.border = 'none';
                props.iframe.style.width = '100%';
                props.iframe.style.height = internal_settings.initial_height; // set iframe default height to initial_height but also listen to postMessage from iframe

                props.iframe.src = props.iframe_src;

                // append iframe to partner's partner container id
                document.getElementById(props.iframe_container_id).appendChild(props.iframe);

                props.iframe.addEventListener('load', function () {
                    // initial postMessage to iframe. it compares between the style object from url and style object within postMessage
                    if (props.custom_style) {
                        var data = {
                            "event_id": 'wepay-style',
                            "ref_id": props.ref_id,
                            "data": {
                                "custom_style": props.custom_style,
                            }
                        };
                        props.iframe.contentWindow.postMessage(data, props.iframe_src);
                    }

                    // add event listener to listen web message from iframe
                    window.addEventListener("message", _receiveMessageHandler, false);
                });
            }
        };

        /**
         *
         * When tokenize function is invoked it posts web message to iframe.
         * It triggers submit action in iframe, and it returns a promise.
         *
         * @return {Promise} [a promise contains token data or failure response]
         *
         */

        var tokenize = function () {

            var promise_key = props.promise_count++;

            var token_promise = new Promise(function (resolve, reject) {
                props.promise_map[promise_key] = {
                    resolve: resolve,
                    reject: reject
                };

                var data = {
                    "event_id": "wepay-tokenize",
                    "ref_id": props.ref_id,
                    "promise_count": promise_key,
                };

                var iframe_element = document.getElementById(props.iframe_id);

                if (!iframe_element) {

                    var error = new WePay._internal.errorCollection();

                    error.addIDNotFoundError("iframe_id", props.iframe_id);

                    if (error.hasError()) {
                        props.promise_map[promise_key].reject(error.buildInvalidParams());
                    }

                } else {
                    // postMessage to the given iframes contentWindow
                    iframe_element.contentWindow.postMessage(data, props.iframe_src);

                    // 5 second timeout and reject the promise
                    setTimeout(function () {
                        if (props.promise_map[promise_key] != null) {

                            var timeout_error = new WePay._internal.errorCollection();
                            props.promise_map[promise_key].reject(timeout_error.buildUnexpectedError());
                            delete props.promise_map[promise_key];
                        }
                    }, 5000);
                }
            });

            return token_promise;
        };

        var setFocus = function (inputKey) {

            var data = {
                "event_id": "wepay-focus",
                "ref_id": props.ref_id,
                "input_key": inputKey,
            };

            var validInputKeys = ["cc-number", "cvv-number", "expiration-month", "expiration-year"];
            if (!validInputKeys.includes(data.input_key)) {
                console.warn(data.input_key + " is not a supported key");
                return;
            }

            var iframe_element = document.getElementById(props.iframe_id);
            iframe_element.contentWindow.postMessage(data, props.iframe_src);

        };

        /**
         * Internal Private Function
         */

        /**
         * _receiveMessageHandler function is a eventHandler to continue listening web message from window.
         */
        var _receiveMessageHandler = function (event) {

            if (event.origin !== props.iframe_endpoint)
                return;

            var event_data = event.data;

            // promise_count is a key from promise_map. we use it to retrieve particular promise's resolve and reject function
            var promise_count = event_data.promise_count;

            // check if event data has promise count
            if (promise_count != null) {

                // check if promise_count in the promise_map.
                // If promise_count key does not exist in promise_map, it is deleted by a bad request from tokenize function (long request)
                if (props.promise_map[promise_count] == null) {
                    // we just return because promise is rejected from tokenize settimeout 30 second
                    return;
                }
            }

            if (event_data.event_id === "wepay-token" && event_data.ref_id === props.ref_id && event_data.data != null) {
                // wepay-token is received success data
                props.promise_map[event.data.promise_count].resolve(event_data.data);
                delete props.promise_map[event.data.promise_count];
            } else if (event_data.event_id === "wepay-token" && event_data.ref_id === props.ref_id && event_data.error != null) {
                // wepay-token is received error data
                props.promise_map[event.data.promise_count].reject(event_data.error);
                delete props.promise_map[event.data.promise_count];
            } else if (event_data.event_id === "wepay-style" && event_data.ref_id === props.ref_id && event_data.error != null) {
                // wepay-style is received error message
                throw event_data.error;
            } else if (event_data.event_id === "wepay-resize" && event_data.ref_id === props.ref_id) {
                // resize the iframe height
                props.iframe.style.height = event_data.height;

                //Â sendÂ acknowledgmentÂ messageÂ backÂ toÂ the iframeÂ soÂ iframeÂ knowsÂ thatÂ tokenization.jsÂ recieveÂ theÂ wepay-sizeÂ event
                event_data.event_id = 'wepay-resize-acknowledged';
                props.iframe.contentWindow.postMessage(event_data, props.iframe_src);
            }

        }.bind(this);

        /**
         * _setUpEnvironment function is to generate iframe url and add query params
         */
        var _generateIframeUrl = function () {

            props.iframe_endpoint = WePay._internal.endpointsMapper('iframe');
            props.ref_id = WePay.tags.uuid();
            props.app_id = WePay._internal.getAppID();
            props.api_version = WePay._internal.getAPIVersion();
            var ref_id = encodeURIComponent(props.ref_id);
            var app_id = encodeURIComponent(props.app_id);
            var api_version = encodeURIComponent(props.api_version);

            props.iframe_src = props.iframe_endpoint + props.iframe_route +
                '?ref_id=' + ref_id +
                '&client_id=' + app_id +
                '&api_version=' + api_version;

            if (props.country_code) {
                props.iframe_src += '&country=' + props.country_code;
            }
            if (props.ssn4_enabled === false) {
                props.iframe_src += '&ssn4_enabled=false';
            }

            // add query params if user is overriding the defaults for labels, placeholders, or errors
            if (props.show_labels === true) {
                props.iframe_src += '&show_labels=true';
            }
            if (props.show_placeholders === false) {
                props.iframe_src += '&show_placeholders=false';
            }
            if (props.show_error_messages === true) {
                props.iframe_src += '&show_error_messages=true';
            }
            if (props.show_error_messages_when_unfocused === false) {
                props.iframe_src += '&show_error_messages_when_unfocused=false';
            }
            if (props.show_error_icon === false) {
                props.iframe_src += '&show_error_icon=false'
            }
            if (props.show_required_asterisk === true) {
              props.iframe_src += '&show_required_asterisk=true'
            }
            if (props.resize_on_error_message === true) {
                props.iframe_src += '&resize_on_error_message=true'
            }
            if (props.custom_required_field_error_message) {
                props.iframe_src += '&custom_required_field_error_message=' + props.custom_required_field_error_message;
            }
            if (props.use_one_liner === true) {
                props.iframe_src += '&use_one_liner=true';
            }

            // check if http query param is not too long and add custom_style param
            var custom_style_param = '#custom_style=' + window.btoa(JSON.stringify(props.custom_style));
            if (props.custom_style && ((props.iframe_src.length + custom_style_param.length) < 2000)) {
                props.iframe_src += custom_style_param;
            }
        };
        /**
         * _exportPropsForUnitTesting function is to print out all private values for unit testing purposes
         * @return {Object} an object is a clone of private props value in _internal_tokenzinationIframe
         */
        var _exportPropsForUnitTesting = function () {
            var event = {
                _receiveMessageHandler: _receiveMessageHandler
            };
            return Object.assign(event, props);
        };

        return {
            init: init,
            tokenize: tokenize,
            setFocus: setFocus,
            test: _exportPropsForUnitTesting
        };

    };

    this.wallet_iframe = function (wallet_type) {

        var props = {
            is_init: false,
            iframe_id: undefined,
            iframe_endpoint: undefined,
            iframe_route: undefined,
            iframe_container_id: undefined,
            button_configs: undefined,
            on_success: undefined,
            on_error: undefined,
        };

        /**
         *  Public Functions
         *
         * @param {string} iframe_container_id value can be used JavaScript to append credit card iframe element on window document.
         *
         * @param {Object} button_configs is an object with configs specific to the type of digital wallet being created
         *
         * @param {object} internal_settings is an object containing internal settings specific to certain iframe types
         * @property {string && required} internal_settings.iframe_route is used to specify which url to call to load the iframe.
         * @property {string && required} internal_settings.initial_height is used to specify with which initial height to load the iframe.
         * @property {boolean && optional} internal_settings.sandbox_enabled is used to specify whether the sandbox attribute should be added to the iframe.
         * @property {string && optional} internal_settings.sandbox_attributes is used to specify the restrictions that should be removed from the sandbox.
         */
        var init = function (iframe_container_id, options, internal_settings) {
            var iframe_options = options || {};

            // only init once
            if (props.is_init) {
                return;
            }

            props.is_init = true;
            props.iframe_container_id = iframe_container_id;
            props.iframe_id = iframe_container_id + "_iframe";
            props.transaction_info = iframe_options.transaction_info;
            props.button_configs = iframe_options.button_configs || {};
            props.account_id = props.button_configs.accountId || null;
            props.iframe_route = internal_settings.iframe_route;
            props.country_code = iframe_options.country_code || null;
            props.sandbox_enabled = internal_settings.sandbox_enabled;
            props.sandbox_attributes = internal_settings.sandbox_attributes;
            props.on_success = iframe_options.on_success || function() {};
            props.on_error = iframe_options.on_error || function() {};
            props.on_update_payment_data = iframe_options.on_update_payment_data || function() {};

            _generateIframeUrl();

            // create an iframe element
            var iframe = document.createElement('iframe');
            iframe.id = props.iframe_id;
            iframe.title = wallet_type;
            if (props.sandbox_enabled) {
                iframe.sandbox = props.sandbox_attributes;
            }
            props.iframe = iframe;

            props.iframe.style.border = 'none';
            props.iframe.style.width = '100%';
            props.iframe.style.height = internal_settings.initial_height; // set iframe default height to initial_height but also listen to postMessage from iframe
            props.iframe.allowPaymentRequest = true;

            props.iframe.src = props.iframe_src;

            // append iframe to partner's partner container id
            document.getElementById(props.iframe_container_id).appendChild(props.iframe);

            props.iframe.addEventListener('load', function () {
                // initial postMessage to iframe. it compares between the style object from url and style object within postMessage
                if (props.button_configs) {
                    var styling_data = {
                        event_id: 'wepay-button-configs',
                        ref_id: props.ref_id,
                        data: {
                            button_configs: props.button_configs,
                        }
                    };
                    props.iframe.contentWindow.postMessage(styling_data, props.iframe_src);
                }

                // add event listener to listen web message from iframe
                window.addEventListener("message", _receiveMessageHandler, false);
            });
        };

        /**
         * Internal Private Function
         */

        /**
         * _receiveMessageHandler function is a eventHandler to continue listening web message from window.
         */
        var _receiveMessageHandler = function (event) {

            var event_data = event.data;

            if (event_data.ref_id !== props.ref_id) {
                return;
            }

            var is_success = event_data.data != null;
            var is_error = event_data.error != null;

            if (event_data.event_id === "wepay-token" && is_success) {
                // wepay-token is received success data
                props.on_success(event_data.data);
            } else if (event_data.event_id === "wepay-token" && is_error) {
                // wepay-token is received error data
                props.on_error(event_data.error);
            } else if (event_data.event_id === "wepay-payment-data-update" && is_success) {
                // cardholder successfully changed "OFFER" | "SHIPPING_OPTION" | "SHIPPING_ADDRESS"
                // on_update_payment_data should return an object containing any changes to
                // payment data
                var updated_payment_data = props.on_update_payment_data(event_data.data) || {};

                var payment_data_response = {
                    event_id: 'wepay-payment-data-update',
                    ref_id: props.ref_id,
                    data: updated_payment_data
                };

                props.iframe.contentWindow.postMessage(payment_data_response, props.iframe_src);
            } else if (event_data.event_id === "wepay-payment-data-update" && is_error) {
                // wepay-payment-data-update returns an error for the partner
                props.on_error(event_data.error);
            }
        }.bind(this);

        /**
         * _setUpEnvironment function is to generate iframe url and add query params
         */
        var _generateIframeUrl = function () {

            props.iframe_endpoint = WePay._internal.endpointsMapper('iframe');
            props.ref_id = WePay.tags.uuid();
            props.app_id = WePay._internal.getAppID();
            props.api_version = WePay._internal.getAPIVersion();
            var ref_id = encodeURIComponent(props.ref_id);
            var app_id = encodeURIComponent(props.app_id);
            var api_version = encodeURIComponent(props.api_version);

            props.iframe_src = props.iframe_endpoint + props.iframe_route +
                '?ref_id=' + ref_id +
                '&client_id=' + app_id +
                '&api_version=' + api_version;

            if (props.account_id) {
                props.iframe_src += '&account_id=' + props.account_id;
            }
        };
        /**
         * _exportPropsForUnitTesting function is to print out all private values for unit testing purposes
         * @return {Object} an object is a clone of private props value in _internal_tokenzinationIframe
         */
        var _exportPropsForUnitTesting = function () {
            var event = {
                _receiveMessageHandler: _receiveMessageHandler
            };
            return Object.assign(event, props);
        };

        return {
            init: init,
            // setFocus: setFocus,
            test: _exportPropsForUnitTesting
        };

    };

    return this;
})();

WePay.risk = WePay.risk || {};

WePay.risk.generate_risk_token = WePay.risk.generate_risk_token || function () {
    WePay._internal.generate_risk_token();
};

WePay.risk.generate_risk_token_onload = WePay.risk.generate_risk_token_onload || function () {
    WePay._internal.generate_risk_token_delayed();
};

WePay.risk.get_risk_token = WePay.risk.get_risk_token || function () {
    return WePay._internal.get_risk_token();
};

// Generate the risk token on body load
try {
    window.addEventListener("load", WePay._internal.generate_risk_token_delayed, false);
} catch (exception) {
    // IE
    window.attachEvent("onload", WePay._internal.generate_risk_token_delayed);
}