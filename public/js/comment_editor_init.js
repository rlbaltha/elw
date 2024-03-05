let editor;
ClassicEditor.create(document.getElementById("comment_body"), {
    toolbar: {
        items: [
            'undo', 'redo',
            'bold', 'italic', 'strikethrough', '|',
            'bulletedList', 'numberedList', '|',
            'outdent', 'indent', '|',
            'fontColor', 'highlight', '|',
            'link'
        ],
        shouldNotGroupWhenFull: true
    },
    })
    .then(newEditor => {
        editor = newEditor;
    });
