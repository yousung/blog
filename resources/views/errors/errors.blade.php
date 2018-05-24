@if (count($errors) > 0)
	<style>
		.toast {
			font-size: 1rem;
		}
	</style>
	<script>
        $(function(){
            toastr.options = {
                "closeButton": false,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "3000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

	        @foreach ($errors->all() as $error)
                toastr["error"]("{{ $error }}");
	        @endforeach

	        @foreach ($errors->keys() as $error)
	        @if($loop->first)
	        location.href = '#{{ $error }}';
            $('#{{ $error }}').focus();
	        @endif
	        @endforeach
        })
	</script>
@endif