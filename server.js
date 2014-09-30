var ZMR = ZMR || {};


(function ($) {

    ZMR.API = {

        RELEASED: 'released',
        LOCKED: 'locked',
        FORMS_NAMESPACE: 'zmr:forms:'

    };

    /**
     * Abstract socket cli
     * @type {{session: null, SERVER_URL: string, clientColor: null, sessionId: null, formLocked: boolean, init: Function, initEvents: Function, formEdit: Function, clickReleaseEvent: Function, clickLockEvent: Function, connect: Function, connectedEvent: Function, closedEvent: Function, subscribeEvent: Function, lockForm: Function, releaseForm: Function, log: Function}}
     */
    ZMR.WebSocketClient = {

        session: null,
        SERVER_URL: 'ws://33.33.33.100:8001',
        clientColor: null,
        sessionId: null,
        formLocked: false,


        init: function () {

            this.box = $('#box');
            this.initEvents();
            this.connect();


        },

        initEvents: function () {

            $('#releaseForm').on('click', $.proxy(this.clickReleaseEvent, this));
            $('#lockForm').on('click', $.proxy(this.clickLockEvent, this));
            this.box.on('focus input', $.proxy(this.formEdit, this));

        },

        formEdit: function (e) {

            var target = $(e.currentTarget);

            if (!this.formLocked) {

                this.lockForm(ZMR.API.FORMS_NAMESPACE + target.attr('id'));

            }

        },

        clickReleaseEvent: function (e) {

            e.preventDefault();
            this.releaseForm(ZMR.API.FORMS_NAMESPACE + this.determineFormIdForTarget($(e.currentTarget)));

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
         * @returns {window.ab.Session}
         */
        connect: function () {

            this.session = new ab.Session(
                this.SERVER_URL,
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

        subscribeAllForms: function () {

            //
            $('form').each(function () {

                var formId = this.id;

                ZMR.WebSocketClient.subscribeForm(formId);

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

                    this.box.css('border', 'none');
                    ZMR.WebSocketClient.formLocked = false;
                    $('.actions').hide();
                    break;

                case ZMR.API.LOCKED:

                    ZMR.WebSocketClient.box.css('border', '5px solid red');
                    ZMR.WebSocketClient.box.css('disabled', 'true');
                    ZMR.WebSocketClient.formLocked = true;
                    break;

            }

        },

        lockForm: function (formName) {

            this.session.call('lockForm', {
                locker: this.sessionId,
                form: formName
            }).then(
                function (response) {
                    ZMR.WebSocketClient.formLocked = true;
                    ZMR.WebSocketClient.box.css('border', '5px solid green');

                    $('.actions').show();
                },
                function (response) {
                    ZMR.WebSocketClient.box.css('border', '5px solid red');
                    ZMR.WebSocketClient.box.css('disabled', 'true');
                    ZMR.WebSocketClient.formLocked = true;
                }
            );


        },
        releaseForm: function (formName) {

            this.session.call('releaseForm', {
                form: formName
            }).then(
                function (response) {
                },
                function (response) {
                }
            );


        },

        log: function (message) {

            this.box.append('<p>' + message + '</p>');

        }

    }

    $(function () {

        ZMR.WebSocketClient.init();

    });


}(jQuery));