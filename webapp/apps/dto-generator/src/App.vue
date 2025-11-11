<template>
  <div class="min-h-screen bg-gray-100 py-8">
    <div class="mx-auto max-w-[100rem] px-4">
      <header class="mb-8 text-center">
        <h1 class="mb-2 text-3xl font-bold text-secondary">
          <span class="mr-2 text-primary">⚙️</span>
          DTO Generator
        </h1>
        <p class="text-gray-600">通过 JSON/JSON5 快速生成 PHP DTO 代码</p>
      </header>

      <div class="flex flex-col gap-6 lg:flex-row">
        <section class="lg:w-1/2">
          <div class="flex h-full flex-col rounded-xl bg-white p-6 shadow-md">
            <h2 class="mb-4 border-b pb-3 text-2xl font-semibold text-secondary">输入配置</h2>
            <form class="flex grow flex-col" @submit.prevent="handleGenerate">
              <div class="mb-4">
                <p class="mb-2 text-sm font-semibold text-gray-700">生成类型</p>
                <div class="flex flex-wrap gap-4 text-sm">
                  <label class="inline-flex items-center gap-2">
                    <input
                      type="radio"
                      class="text-primary"
                      value="dto"
                      v-model="formState.generationType"
                    />
                    基础 DTO
                  </label>
                  <label class="inline-flex items-center gap-2">
                    <input
                      type="radio"
                      class="text-primary"
                      value="form"
                      v-model="formState.generationType"
                    />
                    Form DTO
                  </label>
                </div>
              </div>

              <div v-if="formState.generationType === 'dto'" class="space-y-4">
                <div>
                  <label class="mb-2 block text-sm font-semibold text-gray-700" for="dto-class-name">DTO 类名</label>
                  <input
                    id="dto-class-name"
                    v-model="formState.dto.className"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary"
                    :placeholder="dtoClassPlaceholder"
                    required
                  />
                </div>

                <div>
                  <label class="mb-2 block text-sm font-semibold text-gray-700" for="use-constructor">属性生成方式</label>
                  <select
                    id="use-constructor"
                    v-model="formState.dto.useConstructor"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary"
                  >
                    <option value="">自动（基于基类）</option>
                    <option value="1">构造函数属性提升</option>
                    <option value="0">公开属性</option>
                  </select>
                </div>

                <JsonEditor
                  v-model="formState.dto.jsonInput"
                  title="JSON 数据"
                  @format-success="handleFormatSuccess"
                  @format-error="handleFormatError"
                />
              </div>

              <div v-else class="space-y-4">
                <div>
                  <label class="mb-2 block text-sm font-semibold text-gray-700" for="form-class-name">Form 类名</label>
                  <input
                    id="form-class-name"
                    v-model="formState.form.className"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary"
                    :placeholder="formClassPlaceholder"
                    required
                  />
                </div>

                <JsonEditor
                  v-model="formState.form.requestJson"
                  title="Request JSON"
                  @format-success="handleFormatSuccess"
                  @format-error="handleFormatError"
                />

                <JsonEditor
                  v-model="formState.form.responseJson"
                  title="Response JSON"
                  @format-success="handleFormatSuccess"
                  @format-error="handleFormatError"
                />
              </div>

              <div class="mt-4 flex justify-end">
                <button
                  type="submit"
                  :disabled="loading"
                  class="inline-flex items-center rounded-lg bg-primary px-5 py-2 text-white shadow transition hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-70"
                >
                  <span class="mr-2" v-if="loading">⏳</span>
                  <span class="mr-2" v-else>⚡️</span>
                  {{ loading ? '生成中...' : '生成 DTO' }}
                </button>
              </div>
            </form>
          </div>
        </section>

        <section class="lg:w-1/2">
          <div class="flex h-full flex-col rounded-xl bg-white p-6 shadow-md">
            <div class="mb-4 flex items-center justify-between border-b pb-3">
              <h2 class="text-2xl font-semibold text-secondary">生成结果</h2>
              <div v-if="hasResult" class="flex gap-2">
                <button
                  type="button"
                  class="rounded-lg bg-success px-3 py-1 text-sm text-white shadow hover:bg-green-600"
                  @click="copyToClipboard"
                >
                  复制
                </button>
                <button
                  type="button"
                  class="rounded-lg bg-secondary px-3 py-1 text-sm text-white shadow hover:bg-gray-700"
                  @click="downloadCode"
                >
                  下载
                </button>
              </div>
            </div>

            <div v-if="hasResult" class="flex grow flex-col">
              <p class="mb-2 text-sm text-gray-600">
                类名：<span class="font-semibold">{{ resultMeta?.className }}</span>
              </p>
              <CodeEditor v-model="resultCode" lang="php" :readOnly="true" height="380px" />
            </div>

            <div v-else class="flex grow flex-col items-center justify-center text-gray-400">
              <span class="mb-4 text-5xl">🧩</span>
              <p class="text-lg">生成的 DTO 会展示在这里</p>
              <p class="text-sm">填写表单并点击“生成 DTO”即可</p>
            </div>
          </div>
        </section>
      </div>
    </div>

    <transition name="notification">
      <div
        v-if="notification.show"
        class="fixed right-5 top-5 z-50 rounded-lg px-4 py-3 font-semibold text-white shadow-lg"
        :class="notification.type === 'success' ? 'bg-success' : 'bg-danger'"
      >
        {{ notification.message }}
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import JSON5 from 'json5';
import JsonEditor from './components/JsonEditor.vue';
import CodeEditor from './components/CodeEditor.vue';
import { DtoGenerator, type JsonRecord, type JsonValue } from './lib/dtoGenerator';

type GenerationType = 'dto' | 'form';

type NotificationType = 'success' | 'error';

type UiConfig = {
  defaultGenerationType: GenerationType;
  defaultNamespace: string;
};

const DEFAULT_UI_CONFIG: UiConfig = {
  defaultGenerationType: 'dto',
  defaultNamespace: 'App\\DTO'
};

const resolveUiConfig = (): UiConfig => {
  if (typeof window === 'undefined') {
    return DEFAULT_UI_CONFIG;
  }

  const query = new URLSearchParams(window.location.search);
  const queryConfig: Partial<UiConfig> = {};

  const queryGenerationType = query.get('defaultGenerationType');
  if (queryGenerationType === 'dto' || queryGenerationType === 'form') {
    queryConfig.defaultGenerationType = queryGenerationType;
  }

  const queryNamespace = query.get('defaultNamespace');
  if (queryNamespace) {
    queryConfig.defaultNamespace = queryNamespace;
  }

  const merged = {
    ...DEFAULT_UI_CONFIG,
    ...(window.__DTO_GENERATOR_CONFIG ?? {}),
    ...queryConfig
  };

  const normalizedNamespace = (merged.defaultNamespace ?? DEFAULT_UI_CONFIG.defaultNamespace).trim();

  return {
    defaultGenerationType: merged.defaultGenerationType === 'form' ? 'form' : 'dto',
    defaultNamespace: normalizedNamespace || DEFAULT_UI_CONFIG.defaultNamespace
  };
};

const uiConfig = resolveUiConfig();
const namespacePrefix = uiConfig.defaultNamespace.replace(/\\+$/, '');
const buildClassName = (suffix: string) => (namespacePrefix ? `${namespacePrefix}\\${suffix}` : suffix);
const dtoClassPlaceholder = buildClassName('UserDTO');
const formClassPlaceholder = buildClassName('UserSearchForm');

const dtoExample = {
  name: 'John Doe',
  email: 'john@example.com',
  '?age': 30,
  height: 180.5,
  '//height': '身高',
  has_child: true,
  address: {
    city: 'New York',
    '?street': '123 Main St'
  },
  phones: [
    {
      number: '123-456-7890',
      type: 'home',
      '//type': '类型'
    },
    {
      number: '987-654-3210',
      type: 'work'
    }
  ]
};

const requestExample = {
  '?search': 'John',
  '//search': '搜索关键词'
};

const responseExample = {
  count: 1,
  list: [
    {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
      '//email': '邮箱',
      address: {
        street: '123 Main St',
        city: 'New York'
      },
      phones: [
        {
          number: '123-456-7890',
          type: 'home',
          '//type': '类型'
        },
        {
          number: '987-654-3210',
          type: 'work'
        }
      ]
    }
  ]
};

const formState = reactive({
  generationType: uiConfig.defaultGenerationType,
  dto: {
    className: dtoClassPlaceholder,
    useConstructor: '',
    jsonInput: JSON.stringify(dtoExample, null, 2)
  },
  form: {
    className: formClassPlaceholder,
    requestJson: JSON.stringify(requestExample, null, 2),
    responseJson: JSON.stringify(responseExample, null, 2)
  }
});

const generator = new DtoGenerator();
const loading = ref(false);
const resultMeta = ref<{ className: string } | null>(null);
const resultCode = ref('');
const hasResult = computed(() => Boolean(resultMeta.value));

const notification = reactive({
  show: false,
  message: '',
  type: 'success' as NotificationType
});

let notificationTimer: number | null = null;

const showNotification = (message: string, type: NotificationType = 'success') => {
  notification.message = message;
  notification.type = type;
  notification.show = true;
  if (notificationTimer) {
    window.clearTimeout(notificationTimer);
  }
  notificationTimer = window.setTimeout(() => {
    notification.show = false;
  }, 2800);
};

const handleFormatSuccess = (message: string) => showNotification(message, 'success');
const handleFormatError = (message: string) => showNotification(message, 'error');

const parseJsonValue = (value: string, label: string, allowEmpty = false, emptyFallback: JsonValue = {} as JsonRecord): JsonValue => {
  if (!value.trim()) {
    if (allowEmpty) {
      return emptyFallback;
    }
    throw new Error(`${label} 不能为空`);
  }
  try {
    return JSON5.parse(value);
  } catch (error) {
    throw new Error(`${label} JSON 解析失败：${(error as Error).message}`);
  }
};

const ensureObject = (value: JsonValue, label: string): JsonRecord => {
  if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
    return value as JsonRecord;
  }
  throw new Error(`${label} 根节点必须是一个对象`);
};

const resolveUseConstructor = (): boolean | null => {
  if (formState.dto.useConstructor === '') {
    return null;
  }
  return formState.dto.useConstructor === '1';
};

const generateDto = () => {
  const className = formState.dto.className.trim();
  if (!className) {
    throw new Error('请输入 DTO 类名');
  }
  const jsonData = parseJsonValue(formState.dto.jsonInput, 'JSON 数据');
  const normalized = ensureObject(jsonData, 'JSON 数据');
  generator.setUseConstructor(resolveUseConstructor());
  const code = generator.generate(className, normalized);
  resultCode.value = code;
  resultMeta.value = { className };
};

const generateForm = () => {
  const className = formState.form.className.trim();
  if (!className) {
    throw new Error('请输入 Form DTO 类名');
  }
  const requestData = parseJsonValue(formState.form.requestJson, 'Request JSON');
  const responseData = parseJsonValue(formState.form.responseJson, 'Response JSON', true, {} as JsonRecord);
  const requestObject = ensureObject(requestData, 'Request JSON');
  const responseObject = ensureObject(responseData, 'Response JSON');
  generator.setUseConstructor(resolveUseConstructor());
  const code = generator.generateForm(className, requestObject, responseObject);
  resultCode.value = code;
  resultMeta.value = { className };
};

const handleGenerate = () => {
  loading.value = true;
  try {
    if (formState.generationType === 'dto') {
      generateDto();
    } else {
      generateForm();
    }
    showNotification('DTO 生成成功！', 'success');
  } catch (error) {
    resultMeta.value = null;
    resultCode.value = '';
    showNotification((error as Error).message, 'error');
  } finally {
    loading.value = false;
  }
};

const copyToClipboard = async () => {
  if (!resultCode.value) {
    showNotification('暂无可复制的代码', 'error');
    return;
  }
  try {
    await navigator.clipboard.writeText(resultCode.value);
    showNotification('代码已复制', 'success');
  } catch (_) {
    const textarea = document.createElement('textarea');
    textarea.value = resultCode.value;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    showNotification('代码已复制', 'success');
  }
};

const downloadCode = () => {
  if (!resultCode.value || !resultMeta.value) {
    showNotification('暂无可下载的代码', 'error');
    return;
  }
  const classSegments = resultMeta.value.className.split('\\');
  const fileName = `${classSegments[classSegments.length - 1] || 'DTO'}.php`;
  const blob = new Blob([resultCode.value], { type: 'application/php' });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = fileName;
  document.body.appendChild(anchor);
  anchor.click();
  document.body.removeChild(anchor);
  URL.revokeObjectURL(url);
  showNotification('开始下载文件', 'success');
};

</script>
