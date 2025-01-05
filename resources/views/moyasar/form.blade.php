<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <!-- Other elements -->
    <!-- Moyasar Styles -->
    <link rel="stylesheet" href="https://cdn.moyasar.com/mpf/1.2.0/moyasar.css">

    <!-- Moyasar Scripts -->
    <script src="https://polyfill.io/v3/polyfill.min.js?features=fetch"></script>
    <script src="https://cdn.moyasar.com/mpf/1.2.0/moyasar.js"></script>

</head>
<body>
<!-- Moyasar Payment Form -->
<div class="mysr-form"></div>
<script>
    Moyasar.init({
        // Required
        // Specify where to render the form
        // Can be a valid CSS selector and a reference to a DOM element
        element: '.mysr-form',

        // Required
        // Amount in the smallest currency unit
        // For example:
        // 10 SAR = 10 * 100 Halalas
        // 10 KWD = 10 * 1000 Fils
        // 10 JPY = 10 JPY (Japanese Yen does not have fractions)
        amount: {{ $webForm->amount }},

        // Required
        // Currency of the payment transation
        currency: '{{ $webForm->currency }}',

        // Required
        // A small description of the current payment process
        description: '{{ $webForm->description }}',

        // Required
        publishable_api_key: '{{ env('MOYASAR_API_PUBLISHABLE_KEY') }}',
        // Required
        // This URL is used to redirect the user when payment process has completed
        // Payment can be either a success or a failure, which you need to verify on you system (We will show this in a couple of lines)
        callback_url: "{{ route('MoyasarPaymentCallback') }}",

        metadata: {
            "web_form_id": '{{ $webForm->moyasar_web_form_uuid }}'
        },

        //TODO: ADD success, error methods
        on_failure: function (error) {
            window.location.href = '{{ route('MoyasarWebFormFailed', ['id' => $webForm->moyasar_web_form_uuid]) }}?error='+error;
            // Handle error
        },

        // Optional
        // Required payments methods
        // Default: ['creditcard', 'applepay', 'stcpay']
        methods: [
            'creditcard',
        ],
    });
</script>
</body>
</html>
