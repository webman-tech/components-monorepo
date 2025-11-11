<template>
  <div class="mb-4">
    <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2 text-gray-700">
        <span class="font-semibold">{{ title }}</span>
        <div v-if="showTips" class="group relative cursor-pointer select-none">
          <span class="flex h-5 w-5 items-center justify-center rounded-full border border-gray-300 text-xs font-semibold text-gray-500">?</span>
          <div
            class="tooltip pointer-events-none absolute z-20 mt-2 w-72 rounded-lg bg-gray-800 p-3 text-xs text-white opacity-0 shadow-lg transition-all duration-200 group-hover:opacity-100 group-hover:translate-y-1"
          >
            <p class="mb-1 font-semibold">JSON 支持说明：</p>
                    <ul class="list-disc space-y-1 pl-4">
                        <li>支持 JSON5 输入（注释、无引号键名等）</li>
                        <li>格式化会输出标准 JSON</li>
                        <li>"//字段" 可描述对应字段含义</li>
                        <li>字段前缀 "?" 表示可为 null</li>
                        <li>快捷键：Ctrl+D 向下复制当前行</li>
                    </ul>
          </div>
        </div>
      </div>
      <button
        type="button"
        @click="handleFormat"
        class="rounded bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
      >
        格式化 JSON
      </button>
    </div>
    <CodeEditor
      v-model="modelValueRef"
      lang="json5"
      :height="height"
      @ready="forwardEditor"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import JSON5 from 'json5';
import CodeEditor from './CodeEditor.vue';

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  modelValue: {
    type: String,
    default: ''
  },
  height: {
    type: String,
    default: '360px'
  },
  showTips: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['update:modelValue', 'format-success', 'format-error', 'ready']);

const modelValueRef = computed({
  get: () => props.modelValue ?? '',
  set: value => {
    const normalized = value ?? '';
    emit('update:modelValue', normalized);
  }
});

const handleFormat = () => {
  const source = modelValueRef.value;
  if (!source) {
    emit('format-error', '没有可格式化的内容');
    return;
  }
  try {
    const parsed = JSON5.parse(source);
    const formatted = JSON.stringify(parsed, null, 2);
    modelValueRef.value = formatted;
    emit('format-success', 'JSON 格式化成功');
  } catch (error) {
    emit('format-error', `JSON/JSON5 格式错误: ${(error as Error).message}`);
  }
};

const forwardEditor = (editor: unknown) => {
  emit('ready', editor);
};
</script>

<style scoped>
.tooltip {
  visibility: hidden;
  opacity: 0;
}

.group:hover .tooltip {
  visibility: visible;
  opacity: 1;
}

.tooltip::before {
  content: '';
  position: absolute;
  top: -8px;
  left: 12px;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-bottom: 8px solid #1f2937;
}
</style>
