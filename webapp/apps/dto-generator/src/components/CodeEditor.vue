<template>
  <div ref="editorRoot" class="code-editor"></div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { Extension } from '@codemirror/state';
import { Compartment, EditorState, Transaction } from '@codemirror/state';
import {
  EditorView,
  keymap,
  highlightActiveLine,
  drawSelection,
  lineNumbers
} from '@codemirror/view';
import { copyLineDown, history, historyKeymap } from '@codemirror/commands';
import { defaultHighlightStyle, indentOnInput, syntaxHighlighting } from '@codemirror/language';
import { json } from '@codemirror/lang-json';
import { php } from '@codemirror/lang-php';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  lang: {
    type: String,
    default: 'json'
  },
  readOnly: {
    type: Boolean,
    default: false
  },
  height: {
    type: String,
    default: '360px'
  }
});

const emit = defineEmits(['update:modelValue', 'ready']);

const editorRoot = ref<HTMLDivElement | null>(null);
let view: EditorView | null = null;
let applyingDocChange = false;

const languageCompartment = new Compartment();
const readOnlyCompartment = new Compartment();
const themeCompartment = new Compartment();

const createLanguageExtension = (lang: string): Extension => {
  if (lang === 'php') {
    return php();
  }
  return json();
};

const createThemeExtension = (height: string): Extension => {
  return EditorView.theme({
    '&': {
      minHeight: height,
      border: '1px solid #e5e7eb',
      borderRadius: '0.5rem',
      backgroundColor: '#fff'
    },
    '.cm-scroller': {
      fontFamily:
        `'JetBrains Mono', 'Fira Code', 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace`,
      lineHeight: '1.5',
      padding: '0.75rem'
    },
    '.cm-content': {
      fontSize: '0.9rem'
    },
    '.cm-gutters': {
      backgroundColor: 'transparent',
      border: 'none',
      color: '#94a3b8'
    }
  });
};

const createReadOnlyExtension = (readOnly: boolean): Extension => {
  if (!readOnly) {
    return EditorView.editable.of(true);
  }
  return [
    EditorView.editable.of(true),
    EditorState.transactionFilter.of((tr: Transaction) => {
      if (!tr.docChanged) {
        return tr;
      }
      const userEvent = tr.annotation(Transaction.userEvent);
      if (userEvent) {
        return [];
      }
      return tr;
    })
  ];
};

const createState = () =>
  EditorState.create({
    doc: props.modelValue ?? '',
    extensions: [
      lineNumbers(),
      highlightActiveLine(),
      drawSelection(),
      history(),
      keymap.of([
        {
          key: 'Mod-a',
          preventDefault: true,
          run: view => {
            view.dispatch({
              selection: { from: 0, to: view.state.doc.length }
            });
            return true;
          }
        },
        {
          key: 'Mod-d',
          preventDefault: true,
          run: view => {
            copyLineDown(view);
            return true;
          }
        },
        ...historyKeymap
      ]),
      indentOnInput(),
      syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
      EditorView.lineWrapping,
      EditorState.tabSize.of(2),
      languageCompartment.of(createLanguageExtension(props.lang)),
      readOnlyCompartment.of(createReadOnlyExtension(props.readOnly)),
      themeCompartment.of(createThemeExtension(props.height)),
      EditorView.updateListener.of(update => {
        if (update.docChanged && !applyingDocChange) {
          emit('update:modelValue', update.state.doc.toString());
        }
      })
    ]
  });

onMounted(() => {
  if (!editorRoot.value) {
    return;
  }
  view = new EditorView({
    state: createState(),
    parent: editorRoot.value
  });
  emit('ready', view);
});

watch(
  () => props.modelValue,
  value => {
    if (!view) {
      return;
    }
    const doc = view.state.doc.toString();
    const target = value ?? '';
    if (doc === target) {
      return;
    }
    applyingDocChange = true;
    view.dispatch({
      changes: { from: 0, to: doc.length, insert: target }
    });
    applyingDocChange = false;
  }
);

watch(
  () => props.lang,
  lang => {
    if (!view) {
      return;
    }
    view.dispatch({
      effects: languageCompartment.reconfigure(createLanguageExtension(lang))
    });
  }
);

watch(
  () => props.readOnly,
  readOnly => {
    if (!view) {
      return;
    }
    view.dispatch({
      effects: readOnlyCompartment.reconfigure(createReadOnlyExtension(readOnly))
    });
  }
);

watch(
  () => props.height,
  height => {
    if (!view) {
      return;
    }
    view.dispatch({
      effects: themeCompartment.reconfigure(createThemeExtension(height))
    });
  }
);

onBeforeUnmount(() => {
  view?.destroy();
  view = null;
});
</script>

<style scoped>
.code-editor :deep(.cm-editor) {
  border-radius: 0.5rem;
}
</style>
