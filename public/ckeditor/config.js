CKEDITOR.editorConfig = function( config ) {
    config.toolbar = 'Full';
    config.language = 'ko';
    config.font_names = '맑은 고딕; 돋움; 바탕; 돋음; 궁서; Nanum Gothic Coding; Quattrocento Sans;' + CKEDITOR.config.font_names;

    config.extraPlugins = 'youtube,wenzgmap';

    config.toolbar = 'Admin';

    config.toolbar_Admin = [
        {
            name : 'font', 'items' : ['Font','FontSize','TextColor','BGColor','Bold','Italic',
                'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock']
        },
        {
            name : 'style', 'items' : ['Link','Unlink','Table','HorizontalRule','Smiley']
        },
        {
            name: 'tools', 'items' : ['Image', 'zuploader','wenzgmap','Youtube','Syntaxhighlight']
        }
    ];

    config.fontSize_defaultLabel = '16px';
    config.contentsCss = ["body {font-size: 16px;}"];
    config.extraPlugins = 'image,wenzgmap,youtube,zuploader,syntaxhighlight';
    config.enterMode = CKEDITOR.ENTER_BR;

    //youtube
    config.youtube_width = '640';
    config.youtube_height = '480';
    config.youtube_related = true;
    config.youtube_older = false;
    config.youtube_privacy = false;
    config.youtube_responsive = true;
    config.youtube_controls = true;
};
