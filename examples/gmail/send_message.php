<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gmail test cases</title>
    <link rel="stylesheet" href="../css/bootstrap.css"/>
    <link href="../css/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dropzone.5.9.3.min.css" type="text/css"/>
    <script src="../js/jquery-3.6.3.min.js"></script>
    <script src="../js/quill-1.3.4.js"></script>
    <script src="../js/dropzone.5.9.3.min.js"></script>
</head>
<body>
<div class="container" style="margin-top: 50px">
    <div>
        <div class="mb-3 row">
            <label for="create_email_title" class="col-sm-2 col-form-label">Title: </label>
            <div class="col-sm-10">
                <input type="text" class="form-control" id="create_email_title">
            </div>
        </div>
    </div>
    <div>
        <div class="mb-3 row">
            <label for="create_email_to" class="col-sm-2 col-form-label">To: </label>
            <div class="col-sm-10">
                <div class="arrbox" id="toArr">
                </div>
                <input type="email" class="form-control inputTag" id="create_email_to">
            </div>
        </div>
    </div>
    <div>
        <div class="mb-3 row">
            <label for="create_email_cc" class="col-sm-2 col-form-label">cc: </label>
            <div class="col-sm-10">
                <div class="arrbox" id="ccArr">
                </div>
                <input type="email" class="form-control inputTag" id="create_email_cc" autocomplete="off">
            </div>
        </div>
    </div>
    <div>
        <input id="quill_img1" type="file" style="display: none;"/>
        <div id="editor1" style="min-height:200px">
        </div>
    </div>
    <div class="">
        <div class="mb-3 row" style="margin-top: 10px;">
            <div class="col-sm-5">
                <div class="dropzone dz-clickable upload_file_form1">
                    <div class="dz-default dz-message" data-dz-message="">
                        <span>Drop files here to upload</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-7">
                <button type="submit"
                        class="btn-send-email btn btn-primary float-right"
                        style="align-self: center;">
                    Send Email
                </button>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<script type="text/javascript">
    Dropzone.autoDiscover = false;
    document.addEventListener("DOMContentLoaded", function () {
        window.attachments = {};
        let upload = function (file) {
            let formData = new FormData();
            formData.append('file', file);
            $.ajax({
                url: '../upload.php',
                type: 'post',
                data: formData,
                dataType: 'json',
                cache: false,
                traditional: true,
                contentType: false,
                processData: false,
                // async: false,
                success: function (res) {
                    //图片上传成功之后的回调
                    const range = editor.getSelection();
                    if (range) {
                        editor.insertEmbed(range.index, 'image', "" + res.path);
                    }
                }
            });
        }
        let initEditor = function (uid) {
            let toolbarOptions = [
                [{'size': ['small', false, 'large', 'huge']}],  // 用户自定义下拉
                [{'font': []}],
                ['bold', 'italic', 'underline', 'strike'],        // 切换按钮
                ['blockquote', 'code-block'],
                [{'header': 1}, {'header': 2}],               // 用户自定义按钮值
                [{'list': 'ordered'}, {'list': 'bullet'}],
                [{'script': 'sub'}, {'script': 'super'}],      // 上标/下标
                [{'indent': '-1'}, {'indent': '+1'}],          // 减少缩进/缩进
                [{'direction': 'rtl'}],                         // 文本下划线
                // [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{'color': []}, {'background': []}],          // 主题默认下拉，使用主题提供的值
                [{'align': []}],
                ['link', 'image', 'video'],
                ['clean']                                         // 清除格式
            ];


            let options = {
                // debug: 'info',
                modules: {
                    toolbar: {
                        container: toolbarOptions,
                        handlers: {
                            image: function image() {
                                document.getElementById('quill_img' + uid).click();
                            }
                        }
                    },
                },
                placeholder: 'Type something...',
                // readOnly: true,
                strict: true,
                theme: 'snow'
            };
            let editor = new Quill('#editor' + uid, options);
            $('body').on('change', '#quill_img' + uid, function () {
                let file = this.files;
                if (file === undefined || file.length === 0) {
                    return;
                }
                upload(file[0]);
            })
            editor.root.addEventListener("paste", (e) => {
                const clipboardData = e.clipboardData
                // support cut by software & copy image file directly
                const isImage = clipboardData.types.length && clipboardData.types.join('').includes('Files');
                if (!isImage) {
                    return;
                }
                // only support single image paste
                const file = clipboardData.files[0];
                if (!file || !file.name || !(file.name.toLowerCase().indexOf(".png") !== -1 || file.name.toLowerCase().indexOf(".gif") !== -1
                    || file.name.toLowerCase().indexOf(".jpg") !== -1 || file.name.toLowerCase().indexOf(".jpeg") !== -1)) {
                    console.log('粘贴的不是图片')
                    return;
                }
                upload(file);
            });
            editor.clipboard.addMatcher('IMG', (node, delta) => {
                const Delta = Quill.import('delta')
                return new Delta().insert('')
            })
            editor.getHTML = () => {
                return editor.root.innerHTML;
            };
            editor.setHTML = (eh) => {
                editor.root.innerHTML = eh;
            };
            return editor;
        }
        let initUpload = function (uid) {
            let myDropzoneNote = new Dropzone(".upload_file_form" + uid, {
                url: '../upload.php',
                uploadMultiple: true,
                addRemoveLinks: false,
                maxFiles: 10,
                parallelUploads: 5,
                maxFilesize: 200,
                dictFileTooBig: "100M",
                dictInvalidFileType: "Invalid file type",
                success: function (file, response, e) {
                    $('.upload_file_form' + uid).find('.dz-message .dz-button')
                        .text('Drop files here or click here to upload');
                    if (response.status !== 200) {
                        console.log(response.msg);
                    }
                    if (response.status === 200) {
                        if ('1' in window.attachments) {
                            window.attachments[uid] = $.merge(window.attachments[uid], response.names);
                        } else {
                            window.attachments[uid] = response.names;
                        }
                    }
                },
                sending: function (file, xhr, form) {
                }
            });
            myDropzoneNote.on("addedfile", file => {
                console.log(`File added: ${file.name}`);
            });
        }
        let editor = initEditor('1');
        initUpload('1');

        $("body").on('click', '.btn-send-email', function (e) {
            let url = './sender.php';
            let toArr = [];
            $('#create_email_to').siblings('.arrbox').find('.tagspan').each(function () {
                toArr.push($(this).text());
            });
            let ccArr = [];
            $('#create_email_cc').siblings('.arrbox').find('.tagspan').each(function () {
                ccArr.push($(this).text());
            });

            let attachments = '';
            if ('1' in window.attachments) {
                attachments = window.attachments['1'].join(",");
            }
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    'subject': $('#create_email_title').val(),
                    'cc': ccArr.join(', '),
                    'to': toArr.join(', '),
                    'content': editor.getHTML(),
                    'attachments': attachments
                },
                success: function (response) {
                    if (response.status !== 200) {
                        alert(response.msg)
                        return
                    }
                    window.location.reload();
                },
                error: function (jqXHR, exception) {
                    alert('sorry, something is error')
                    console.log([jqXHR, exception])
                },
            })
        }).on('click', '.spanclose', function (e) {
            $(this).parent('.spanbox').remove()
        }).on('keypress', '.inputTag', function (e) {
            if (e.keyCode !== 13) {
                return;
            }
            var isEmail = typeof $(this).checkValidity === 'function' ? $(this).checkValidity() : /\S+@\S+\.\S+/.test($(this).val())
            if (!isEmail) {
                alert('Invalid mailbox');
                return;
            }
            var spanTag = `<div class="spanbox">
                            <span class="tagspan">` + $(this).val() + `</span>
                            <i class="spanclose"></i>
                        </div>`;
            $(this).siblings('.arrbox').append(spanTag);
            $(this).val('')
        });
    })
</script>
<style>
    .ql-editor {
        min-height: 200px;
    }

    /* 外层div */
    .arrbox {
        /*width: 300px;*/
        /*background-color: white;*/
        /*border: 1px solid #dcdee2;*/
        /*border-radius: 4px;*/
        /*font-size: 12px;*/
        /*padding-left: 5px;*/
        box-sizing: border-box;
        text-align: left;
        word-wrap: break-word;
        overflow: hidden;
    }

    /* 标签 */
    .spanbox {
        display: inline-block;
        font-size: 14px;
        margin: 3px 4px 3px 0;
        background-color: #f7f7f7;
        border: 1px solid #e8eaec;
        border-radius: 3px;
    }

    .tagspan {
        height: 24px;
        line-height: 22px;
        max-width: 99%;
        position: relative;
        display: inline-block;
        padding-left: 8px;
        color: #495060;
        font-size: 12px;
        /*cursor: pointer;*/
        opacity: 1;
        vertical-align: middle;
        overflow: hidden;
        transition: 0.25s linear;
    }

    .spanclose {
        padding: 0 10px 5px 0;
        opacity: 1;
        -webkit-filter: none;
        filter: none;
        cursor: pointer;
    }

    .spanclose:after {
        content: "x";
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        line-height: 27px;
    }

    /* input */
    .inputTag {
        /*border: none;*/
        /*width: auto;*/
        /*height: auto;*/
        box-shadow: none;
        outline: none;
        background-color: transparent;
        min-width: 150px;
        vertical-align: top;
        color: #495060;
        line-height: 32px;
    }
</style>