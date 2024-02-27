let editor;
ClassicEditor.create(document.getElementById("doc_body"), {
    wordCount: {
        onUpdate: stats => {
            // Prints the current content statistics.
            const wordCountWrapper = document.getElementById('word-count');
            wordCountWrapper.innerText = stats.words;
        }
    },
    style: {
        definitions: [
            {name: 'Title', element: 'p', classes: ['doc_title']},
            {name: 'Works Cited Title', element: 'p', classes: ['doc_wc']},
            {name: 'Header', element: 'p', classes: ['doc_header']},
            {name: 'Block Quote', element: 'p', classes: ['doc_block_quote']},
            {name: 'Paragraph', element: 'p', classes: ['doc_paragraph_indent']},
            {name: 'Single Space', element: 'p', classes: ['doc_single_space']},
            {name: 'Double Space', element: 'p', classes: ['doc_double_space']},
            {name: 'Hanging Indent', element: 'p', classes: ['doc_hanging_indent']},
        ]
    },
    toolbar: {
        items: [
            'undo', 'redo',
            'style',
            'bold', 'italic', 'strikethrough', '|',
            'bulletedList', 'numberedList', '|',
            'outdent', 'indent', '|',
            'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
            'alignment', '|',
            'link', 'blockQuote', 'insertTable', 'mediaEmbed', '|', 'removeFormat',
            {
                label: 'More styles',
                icon: 'threeVerticalDots',
                items: ['superscript', 'subscript', 'findAndReplace', 'selectAll',
                    'specialCharacters']
            },
        ],
        shouldNotGroupWhenFull: true
    },
    htmlSupport: {
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
    })
    .then(newEditor => {
        editor = newEditor;
        editor.model.document.on('change:data', () => {
            const statusDiv = document.getElementById('editor-status');
            statusDiv.innerText = 'changed';
        });
    });
