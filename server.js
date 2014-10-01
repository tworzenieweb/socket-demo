var ZMR = ZMR || {};


(function ($, noty) {

    ZMR.API = {

        RELEASED: 'released',
        LOCKED: 'locked',
        FORMS_NAMESPACE: 'zmr:forms:'

    };

    /**
     * Abstract websocket client
     *
     */
    ZMR.WebSocketClient = {

        session: null,
        clientColor: null,
        sessionId: null,
        formLocked: false,


        init: function (url) {

            this.initEvents();
            this.connect(url);

        },

        initEvents: function () {

            $('form').on('submit', $.proxy(this.formReleaseEvent, this))
                     .on('focus input', 'input, select, textarea', $.proxy(this.formEditStartedEvent, this));

        },

        formEditStartedEvent: function (e) {

            if (!this.formLocked) {

                this.lockForm(ZMR.API.FORMS_NAMESPACE + this.determineFormIdForTarget($(e.currentTarget)));

            }

        },

        formReleaseEvent: function (e) {

            e.preventDefault();
            this.releaseForm(ZMR.API.FORMS_NAMESPACE + e.currentTarget.id);

        },

        /**
         *
         * @param $target jQuery
         * @return jQuery founded form
         */
        determineFormIdForTarget: function ($target) {

            return $target.closest('form')[0].id;

        },

        /**
         * Connect to websocket and return session instance
         * @param url string websocket connection
         * @returns {window.ab.Session}
         */
        connect: function (url) {

            this.session = new ab.Session(
                url,
                $.proxy(this.connectedEvent, this),
                $.proxy(this.closedEvent, this),
                {
                    'skipSubprotocolCheck': true,
                    'maxRetries': 60,
                    'retryDelay': 2000
                }
            );

        },

        /**
         * Callback called when connection is established
         */
        connectedEvent: function () {

            this.sessionId = this.session._session_id;

            this.subscribeAllForms();

        },

        /**
         * subscribe to all active forms on page
         * (needs to have .lockable class)
         */
        subscribeAllForms: function () {

            var that = this;

            $('form.lockable').each(function () {

                that.subscribeForm(this.id);

            });

        },

        subscribeForm: function (formId) {

            var formKey = ZMR.API.FORMS_NAMESPACE + formId;

            console.log('Subscription for ' + formKey + ' started');
            this.session.subscribe(formKey, $.proxy(this.subscribeEvent, this));

        },

        closedEvent: function () {
            console.warn('WebSocket connection closed');
        },

        subscribeEvent: function(form, payload) {
            switch (payload.status) {

                case ZMR.API.RELEASED:

                    this.releaseUIForm(form);

                    noty({
                        text: 'Form was released',
                        layout: 'top',
                        type: 'success',
                        timeout: 2000
                    });

                    break;

                case ZMR.API.LOCKED:

                    this.lockUIForm(form);

                    noty({
                        text: 'Form was locked by ' + payload.name,
                        layout: 'top',
                        type: 'error',
                        timeout: 2000
                    });

                    break;

            }

        },

        releaseUIForm: function (form) {

            var formKey = form.split(':')[2];

            $('#' + formKey).find('input, textarea, select, button').prop('disabled', false);
            this.formLocked = false;

        },

        lockUIForm: function (form) {

            var formKey = form.split(':')[2];

            $('#' + formKey).find('input, textarea, select, button').prop('disabled', true);
            this.formLocked = true;

        },

        /**4
         * Lock the form by calling server onCall method
         * Then the message is propagated to all subscribers of the form
         * @param formName
         */
        lockForm: function (formName) {

            this.session.call('lockForm', {
                locker: this.sessionId,
                name: "Some user",
                form: formName
            }).then(
                function () {
                    noty({
                        text: 'Form was locked',
                        layout: 'top',
                        type: 'information',
                        timeout: 2000
                    });
                    ZMR.WebSocketClient.formLocked = true;
                },
                function () {
                    ZMR.WebSocketClient.lockUIForm(formName);
                }
            );


        },

        /**
         * Release the form by calling server onCall method
         * Then the message is propagated to all subscribers of the form
         * @param formName
         */
        releaseForm: function (formName) {

            this.session.call('releaseForm', {
                form: formName
            }).then(function () {

                noty({
                    text: 'Form was released',
                    layout: 'top',
                    type: 'information',
                    timeout: 2000
                });

            });


        }

    };


}(jQuery, noty));