CKEDITOR.plugins.add(
    'zuploader',
    {
        icons : 'zuploader',
        init  : function (editor)
        {
            editor.addCommand(
                'zuploaderCommand',
                new CKEDITOR.command(
                    editor,
                    {
                        exec : function (editor)
                        {
                            var action_url = '/admin/file?_token=' + csrfToken;
                            $('body').append("<form id='myForm' action='"+action_url+"' method='post'><input type='file' name='files[]' id='myFile' multiple /></form>");
                            $myForm = $("#myForm");
                            $myFile = $('#myFile');

                            $myForm.ajaxForm({
                                success: function(res, status){
                                    var data = res.data;
                                    var img = null;

                                    $.each(data.pro, function(idx, url){
                                        if(url){
                                            img = CKEDITOR.dom.element.createFromHtml('<p><img class="img-fluid" src="' + url + '"></p>');

                                            try {
                                                CKEDITOR.instances.context.insertElement(img);
                                            } catch (e) {
                                                CKEDITOR.instances.context.insertElement(img);
                                            }
                                            img = null;
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
                                $("#myForm").submit();
                            });
                            $myFile.click();
                        }
                    }
                )
            );

            editor.ui.addButton(
                'zuploader',
                {
                    label   : 'ZzanLAB Uploader',
                    command : 'zuploaderCommand',
                    toolbar : 'zuploader'
                }
            );
        }
    }
);