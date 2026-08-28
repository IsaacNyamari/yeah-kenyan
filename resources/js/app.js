import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Underline from '@tiptap/extension-underline'

/**
 * Rich text editing for article and newsletter bodies.
 *
 * The editor is deliberately limited to the markup ArticleHtml allows through.
 * Anything else it could produce would be stripped on save, and a control that
 * silently does nothing is worse than no control at all.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('richText', (model, live = false) => {
        /*
         * Held in the closure rather than on the component.
         *
         * Alpine makes every data property reactive, which would wrap the
         * editor in a Proxy. ProseMirror compares document and state objects by
         * identity, so a proxied instance makes every transaction look like it
         * belongs to a different editor, and each toolbar button throws
         * "Applying a mismatched transaction".
         */
        let editor = null
        let syncTimer = null

        return {
            /** Mirrors the editor's marks so the toolbar can show what is active. */
            active: {},

            init() {
                editor = new Editor({
                    element: this.$refs.editor,
                    extensions: [
                        StarterKit.configure({
                            // h1 belongs to the page and h2 to its sections, so
                            // an article body starts below both.
                            heading: { levels: [3, 4] },
                            // None of these survive sanitising, so offering
                            // them would be a button that quietly does nothing.
                            codeBlock: false,
                            code: false,
                            horizontalRule: false,
                            link: false,
                        }),
                        Underline,
                        Link.configure({
                            openOnClick: false,
                            autolink: true,
                            protocols: ['http', 'https', 'mailto'],
                        }),
                    ],
                    content: this.$wire.get(model) || '',
                    editorProps: {
                        attributes: { class: 'article-body min-h-[16rem] px-4 py-3' },
                    },
                    onUpdate: ({ editor }) => {
                        this.sync(editor.isEmpty ? '' : editor.getHTML())
                        this.refresh()
                    },
                    onSelectionUpdate: () => this.refresh(),
                    onFocus: () => this.refresh(),
                })

                this.refresh()

                /*
                 * The server writes to this property too — loading a record to
                 * edit, or clearing the form after a save. Without this the
                 * editor would keep showing the previous article.
                 */
                this.$wire.$watch(model, (value) => {
                    const incoming = value || ''

                    if (incoming === (editor.isEmpty ? '' : editor.getHTML())) {
                        return
                    }

                    editor.commands.setContent(incoming, { emitUpdate: false })
                    this.refresh()
                })
            },

            /**
             * Hand the content to Livewire.
             *
             * Deferred by default: a request per keystroke would put the caret
             * at the mercy of the network. Screens with a preview alongside ask
             * for live instead, debounced so round trips stay occasional.
             */
            sync(html) {
                if (!live) {
                    this.$wire.set(model, html, false)

                    return
                }

                clearTimeout(syncTimer)
                syncTimer = setTimeout(() => this.$wire.set(model, html), 600)
            },

            refresh() {
                this.active = {
                    bold: editor.isActive('bold'),
                    italic: editor.isActive('italic'),
                    underline: editor.isActive('underline'),
                    h3: editor.isActive('heading', { level: 3 }),
                    h4: editor.isActive('heading', { level: 4 }),
                    bulletList: editor.isActive('bulletList'),
                    orderedList: editor.isActive('orderedList'),
                    blockquote: editor.isActive('blockquote'),
                    link: editor.isActive('link'),
                }
            },

            run(command) {
                const chain = editor.chain().focus()

                const commands = {
                    bold: () => chain.toggleBold().run(),
                    italic: () => chain.toggleItalic().run(),
                    underline: () => chain.toggleUnderline().run(),
                    h3: () => chain.toggleHeading({ level: 3 }).run(),
                    h4: () => chain.toggleHeading({ level: 4 }).run(),
                    bulletList: () => chain.toggleBulletList().run(),
                    orderedList: () => chain.toggleOrderedList().run(),
                    blockquote: () => chain.toggleBlockquote().run(),
                    undo: () => chain.undo().run(),
                    redo: () => chain.redo().run(),
                    clear: () => chain.unsetAllMarks().clearNodes().run(),
                }

                commands[command]?.()

                this.refresh()
            },

            toggleLink() {
                if (editor.isActive('link')) {
                    editor.chain().focus().unsetLink().run()
                    this.refresh()

                    return
                }

                const href = window.prompt('Link address', 'https://')

                if (!href) {
                    return
                }

                // Anything else is stripped on save anyway, so it is refused
                // here where the reason can be explained.
                if (!/^(https?:|mailto:)/i.test(href)) {
                    window.alert('Links must start with http://, https:// or mailto:')

                    return
                }

                editor.chain().focus().extendMarkRange('link').setLink({ href }).run()
                this.refresh()
            },

            /** Exposed so the browser can drive the editor when testing. */
            instance() {
                return editor
            },

            destroy() {
                clearTimeout(syncTimer)
                editor?.destroy()
            },
        }
    })
})

/**
 * The deploy panel.
 *
 * Driven with plain fetch rather than Livewire on purpose: a deploy replaces
 * the application's code midway, and a Livewire page held open across that
 * change would send the next request with a snapshot the new code cannot
 * hydrate. Ordinary requests carry no such state.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('deployPanel', (config) => ({
        checking: false,
        running: false,
        blocked: '',
        error: '',
        hint: '',
        status: null,
        steps: [],

        async check() {
            this.checking = true
            this.reset()

            try {
                const data = await this.post(config.checkUrl)

                this.blocked = data.blocked || ''
                this.status = data.blocked ? null : data
                this.error = data.error || ''
                this.hint = data.hint || ''
            } catch (e) {
                this.error = e.message
            } finally {
                this.checking = false
            }
        },

        async start() {
            if (!this.status || this.status.upToDate) {
                return
            }

            this.running = true
            this.error = ''
            this.hint = ''
            this.steps = [
                { key: 'pull', label: 'Pulling the latest code', state: 'pending', output: '' },
                { key: 'migrate', label: 'Running database migrations', state: 'pending', output: '' },
                { key: 'assets', label: 'Publishing assets to the web root', state: 'pending', output: '' },
                { key: 'cache', label: 'Rebuilding caches', state: 'pending', output: '' },
            ]

            for (const step of this.steps) {
                step.state = 'running'

                try {
                    const data = await this.post(
                        config.stepUrl.replace('__step__', step.key),
                        { branch: this.status.branch },
                    )

                    step.output = data.output || data.error || ''

                    if (!data.ok) {
                        step.state = 'failed'
                        this.error = data.error || 'The step failed.'
                        this.hint = data.hint || ''
                        this.running = false

                        return
                    }

                    step.state = 'done'
                } catch (e) {
                    step.state = 'failed'
                    this.error = e.message
                    this.running = false

                    return
                }
            }

            this.running = false
            this.done = true

            // The page was rendered by the code that has just been replaced, so
            // it is reloaded rather than left describing a version that is no
            // longer running.
            setTimeout(() => window.location.reload(), 1500)
        },

        done: false,

        reset() {
            this.blocked = ''
            this.error = ''
            this.hint = ''
            this.steps = []
            this.done = false
        },

        async post(url, body = {}) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.token,
                },
                body: JSON.stringify(body),
            })

            if (!response.ok) {
                throw new Error(`The server answered ${response.status}. Check the Laravel log for the reason.`)
            }

            return response.json()
        },
    }))
})
