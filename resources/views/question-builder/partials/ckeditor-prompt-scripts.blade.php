<script src="https://cdn.jsdelivr.net/npm/@ckeditor/ckeditor5-build-classic@41.4.2/build/ckeditor.js" crossorigin="anonymous"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ta = document.querySelector('textarea#prompt');
        if (!ta || typeof ClassicEditor === 'undefined') {
            return;
        }
        ClassicEditor.create(ta, {
            toolbar: [
                'heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', '|',
                'blockQuote', '|', 'undo', 'redo'
            ]
        }).catch(function (e) {
            console.error(e);
        });
    });
</script>
