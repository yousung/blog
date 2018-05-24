/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');
require('sweetalert');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.csrfToken = token.content;
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

try {
    $.confirmDelete = function(delForm){
        swal({
            title: "정말 삭제하시겠습니까?",
            text: "삭제 후에는 복구가 되지 않습니다.",
            icon: "warning",
            buttons: ['취소', '삭제'],
            dangerMode: true,
        })
            .then(function(willDelete){
                if (willDelete) {
                    $(delForm).submit();
                } else {
                    swal('취소', '취소 되었습니다.', 'info');
                }
            });
    };

    $.fileOnLoad = function(src, pro, fileSize){
        let action_url = '/admin/file?_token=' + csrfToken;
        action_url += typeof fileSize !== 'undefined' ? '&fileSize=' + fileSize : '';
        $('body').append("<form id='myForm' action='"+action_url+"' method='post'><input type='file' name='file' id='myFile'/></form>");
        $myForm = $("#myForm");
        $myFile = $('#myFile');
        let originalImage = $(src).attr('src');

        $myForm.ajaxForm({
            success: function(response,status){
                $.each(response, function(i, data){
                    try {
                        $(src).attr('src', data.thumb);
                        $(pro).val(data.pro);
                    } catch (e) {
                        console.log(data.msg);
                        $(src).attr('src', originalImage);
                    }
                });
                $myFile.remove();
                $myForm.remove();
            },
            error: function(){
                alert('error');
            },
        });

        $myFile.on('change', function(){
            $(src).attr('src', '/images/loading.gif');
            $("#myForm").submit();
        });
        $myFile.click();
    }
} catch (e) {}


/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo'

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     encrypted: true
// });
