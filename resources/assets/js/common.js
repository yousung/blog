$(function () {
    var is_touch_device = 'ontouchstart' in document.documentElement;

    if(!is_touch_device){
        $('a').tooltip();
        $('button.none-btn').tooltip();
    }
});