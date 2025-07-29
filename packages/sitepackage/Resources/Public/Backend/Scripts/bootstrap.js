import { Bold, Italic } from '@ckeditor/ckeditor5-basic-styles'
import { ClassicEditor } from '@ckeditor/ckeditor5-editor-classic'
import { Essentials } from '@ckeditor/ckeditor5-essentials'
import { Link } from '@ckeditor/ckeditor5-link'
import { List } from '@ckeditor/ckeditor5-list'
import { Paragraph } from '@ckeditor/ckeditor5-paragraph'
import { SourceEditing } from '@ckeditor/ckeditor5-source-editing'

class MyModule {
    constructor() {
        const target = document.getElementById('message')
        const config = {
            height: '400px',
            licenseKey: 'GPL',
            toolbar: ['bold', 'italic', '|', 'bulletedList', 'numberedList', '|', 'link', '|', 'sourceEditing'],
        }
        ClassicEditor.builtinPlugins = [Essentials, Bold, Italic, List, Link, Paragraph, SourceEditing]
        ClassicEditor.create(target, config)
    }
}

export default new MyModule()
