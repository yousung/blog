@if (Session::has('sweet_alert.alert'))
    <script>
        swal({
            text: "{!! Session::get('sweet_alert.text') !!}",
            title: "{!! Session::get('sweet_alert.title') !!}",
            timer: {!! Session::get('sweet_alert.timer') !!},
            type: "{!! Session::get('sweet_alert.type') !!}",
            icon: "{!! Session::get('sweet_alert.type') !!}",
            showConfirmButton: "{!! Session::get('sweet_alert.showConfirmButton') !!}",
            confirmButtonText: "{!! Session::get('sweet_alert.confirmButtonText') !!}",
            confirmButtonColor: "#AEDEF4"

            // more options
        });
    </script>
    @php
        Session::forget('sweet_alert.alert');
    @endphp

@elseif (Session::has('flash_alert'))
    <script>
        swal({
            text: "{!! Session::get('flash_alert.text') !!}",
            title: "{!! Session::get('flash_alert.title') !!}",
            timer: {!! Session::get('flash_alert.timer') !!},
            type: "{!! Session::get('flash_alert.type') !!}",
            icon: "{!! Session::get('flash_alert.type') !!}",
            showConfirmButton: "{!! Session::get('flash_alert.showConfirmButton') !!}",
            confirmButtonText: "{!! Session::get('flash_alert.confirmButtonText') !!}",
            confirmButtonColor: "#AEDEF4"

            // more options
        });
    </script>
    @php
        Session::forget('flash_alert');
    @endphp
@endif