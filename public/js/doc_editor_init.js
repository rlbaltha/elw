let editor;
ClassicEditor.create(document.getElementById("doc_body"), {
    highlight: {
        options: [
            {
                model: 'yellowMarker',
                class: 'marker-yellow',
                title: 'Yellow marker',
                color: 'var(--ck-highlight-marker-yellow)',
                type: 'marker'
            },
            {
                model: 'greenMarker',
                class: 'marker-green',
                title: 'Green marker',
                color: 'var(--ck-highlight-marker-green)',
                type: 'marker'
            },
            {
                model: 'pinkMarker',
                class: 'marker-pink',
                title: 'Pink marker',
                color: 'var(--ck-highlight-marker-pink)',
                type: 'marker'
            },
            {
                model: 'blueMarker',
                class: 'marker-blue',
                title: 'Blue marker',
                color: 'var(--ck-highlight-marker-blue)',
                type: 'marker'
            },
        ]
    },
    wordCount: {
        onUpdate: stats => {
            // Prints the current content statistics.
            const wordCountWrapper = document.getElementById('word-count');
            wordCountWrapper.innerText = stats.words;
        }
    },
    style: {
        definitions: [
            {name: 'Indented Paragraph', element: 'p', classes: ['doc_paragraph_indent']},
            {name: 'Hanging Indent', element: 'p', classes: ['doc_hanging_indent']},
            {name: 'Single Space', element: 'p', classes: ['doc_single_space']},
            {name: 'Double Space', element: 'p', classes: ['doc_double_space']},
        ]
    },
    toolbar: {
        items: [
            'style',
            'bold', 'italic', 'strikethrough', '|',
            'bulletedList', 'numberedList', '|',
            'outdent', 'indent', '|',
            'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
            'alignment', '|',
            'link', 'blockQuote', 'insertTable','|', 'removeFormat',
            {
                label: 'More styles',
                icon: 'threeVerticalDots',
                items: ['superscript', 'subscript', 'findAndReplace', 'selectAll',
                    'specialCharacters']
            },
        ],
        shouldNotGroupWhenFull: true
    },
    mediaEmbed: {
        previewsInData: true,
    },
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
    })
    .then(newEditor => {
        editor = newEditor;
        editor.model.document.on('change:data', () => {
            const statusDiv = document.getElementById('editor-status');
            statusDiv.innerText = 'changed';
        });
    });
