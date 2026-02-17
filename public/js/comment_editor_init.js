/**
 * This configuration was generated using the CKEditor 5 Builder. You can modify it anytime using this link:
 * https://ckeditor.com/ckeditor-5/builder/#installation/NoNgNARATAdA7DAHBSBGOAWKI6NwZjgFZUBOfABlxFNVRCrhCKn1IyPwzIotMQwoIAUwB2KCmGCowkyTIUBdSMLKoKZCIqA=
 */

import {
    ClassicEditor,
    Autosave,
    Essentials,
    Paragraph,
    Autoformat,
    ImageInsertViaUrl,
    ImageBlock,
    ImageToolbar,
    AutoImage,
    BlockQuote,
    Bold,
    Link,
    Heading,
    ImageCaption,
    ImageInline,
    ImageStyle,
    ImageTextAlternative,
    Indent,
    IndentBlock,
    Italic,
    LinkImage,
    List,
    MediaEmbed,
    Table,
    TableToolbar,
    TableCaption,
    TextTransformation,
    TodoList,
    Underline,
    Emoji,
    Mention,
    Fullscreen,
    Strikethrough,
    Subscript,
    Superscript,
    FontBackgroundColor,
    FontColor,
    FontFamily,
    FontSize,
    Highlight,
    HorizontalLine,
    Alignment,
    Style,
    GeneralHtmlSupport,
    ImageUpload,
    CloudServices
} from 'ckeditor5';

/**
 * Create a free account with a trial: https://portal.ckeditor.com/checkout?plan=free
 */
const LICENSE_KEY = 'GPL'; // or <YOUR_LICENSE_KEY>.

const editorConfig = {
    toolbar: {
        items: [
            'undo', 'redo',
            'bold', 'italic', 'strikethrough', '|',
            'bulletedList', 'numberedList', '|',
            'outdent', 'indent', '|',
            'fontColor', 'highlight', '|',
            'link'
        ],
        shouldNotGroupWhenFull: false
    },
    plugins: [
        Alignment,
        Autoformat,
        AutoImage,
        Autosave,
        BlockQuote,
        Bold,
        CloudServices,
        Emoji,
        Essentials,
        FontBackgroundColor,
        FontColor,
        FontFamily,
        FontSize,
        GeneralHtmlSupport,
        Heading,
        Highlight,
        HorizontalLine,
        ImageBlock,
        ImageCaption,
        ImageInline,
        ImageInsertViaUrl,
        ImageStyle,
        ImageTextAlternative,
        ImageToolbar,
        ImageUpload,
        Indent,
        IndentBlock,
        Italic,
        Link,
        LinkImage,
        List,
        MediaEmbed,
        Mention,
        Paragraph,
        Strikethrough,
        Style,
        Subscript,
        Superscript,
        Table,
        TableCaption,
        TableToolbar,
        TextTransformation,
        TodoList,
        Underline
    ],
    htmlSupport: {
        // elements [attributes]{styles}(classes)
        // span(*){font-size,font-family,color,background-color}[data-id]
        // img{text-align,margin-left}(*); a[!href,target]{*}(*);img[*]{*}
        allow: [
            {
                name: /^(div|p|h2|h3|table|th|tr|td|strong|s|em|ol|ul|li)$/,
                classes: true,
            },
            {
                name: 'span',
                attributes: ['data-id'],
                classes: true,
                styles: ['font-size','font-family','color','background-color']
            },
            {
                name: 'img',
                classes: true,
                styles: true
            },
            {
                name: 'a',
                attributes: ['href','target'],
                classes: true,
                styles: true
            },

        ]
    },
    image: {
        toolbar: ['toggleImageCaption', 'imageTextAlternative', '|', 'imageStyle:inline', 'imageStyle:wrapText', 'imageStyle:breakText']
    },
    licenseKey: LICENSE_KEY,
    link: {
        addTargetToExternalLinks: true,
        defaultProtocol: 'https://',
        decorators: {
            toggleDownloadable: {
                mode: 'manual',
                label: 'Downloadable',
                attributes: {
                    download: 'file'
                }
            }
        }
    },
    mention: {
        feeds: [
            {
                marker: '@',
                feed: [
                    /* See: https://ckeditor.com/docs/ckeditor5/latest/features/mentions.html */
                ]
            }
        ]
    },
    menuBar: {
        isVisible: false
    },
    placeholder: 'Type or paste your content here!',
    table: {
        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
    }
};


ClassicEditor
    .create(document.getElementById("comment_body"), editorConfig)
    .then(editor => {
        window.editor = editor;
        console.log('Editor was initialized', editor);
    })
    .then(() => {
        editor.model.document.on('change:data', () => {
            $('#editor-status').text('Changed');
            console.log('Editor status changed', editor);

        });
    })
    .catch(error => {
        console.error(error);
    });

