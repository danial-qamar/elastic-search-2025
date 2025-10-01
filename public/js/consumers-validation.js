    alert('ok');
    $(document).ready(function () {
        $("#consumerStoreForm").validate({
            rules: {
                reference_no: {
                    required: true,
                    digits: true,
                    minlength: 14,
                    maxlength: 14
                },
                bill_month: {
                    required: true,
                    digits: true,
                    minlength: 6,
                    maxlength: 6
                },
                name: {
                    required: true,
                    minlength: 3
                },
                contactno: {
                    required: true,
                    digits: true,
                    minlength: 13,
                    maxlength: 13
                },
                emailaddr: {
                    email: true
                }
            },
            messages: {
                reference_no: {
                    required: "Reference No is required",
                    digits: "Numbers only",
                    minlength: "Must be 14 digits",
                    maxlength: "Must be 14 digits"
                },
                bill_month: {
                    required: "Bill Month is required",
                    digits: "Numbers only",
                    minlength: "Must be 6 digits (YYYYMM)",
                    maxlength: "Must be 6 digits (YYYYMM)"
                },
                name: {
                    required: "Name is required",
                    minlength: "At least 3 characters"
                },
                contactno: {
                    required: "Contact number is required",
                    digits: "Only numbers allowed",
                    minlength: "At least 11 digits",
                    maxlength: "Maximum 13 digits"
                },
                emailaddr: {
                    email: "Please enter a valid email"
                }
            },
            errorClass: "text-danger",
            errorElement: "span",
            highlight: function (element) {
                $(element).addClass("is-invalid");
            },
            unhighlight: function (element) {
                $(element).removeClass("is-invalid");
            }
        });
    });