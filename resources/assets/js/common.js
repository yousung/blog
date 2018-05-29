$(function () {
    var is_touch_device = 'ontouchstart' in document.documentElement;

    if(!is_touch_device){
        $('a').tooltip();
    }

    var clipboard = new ClipboardJS('.copy-btn');
    clipboard.on('success', function(e) {
        swal('복사완료', '원하시는 곳에 붙여넣기 하세요', 'success');
    });
});