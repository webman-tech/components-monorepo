export type JsonValue = string | number | boolean | null | JsonValue[] | JsonRecord;
export type JsonRecord = { [key: string]: JsonValue };

type GenerateResult = string;

export class DtoGenerator {
  private useConstructor: boolean | null = null;

  setUseConstructor(value: boolean | null): void {
    this.useConstructor = value;
  }

  generate(fullClassName: string, data: JsonRecord): GenerateResult {
    const namespace = this.extractNamespace(fullClassName);
    const className = this.extractClassName(fullClassName);
    const baseClass = this.determineBaseClass(className);
    const useConstructor = this.shouldUseConstructor(baseClass);
    const mainClass = this.generateClass(className, data, baseClass, useConstructor);
    const nestedClasses = this.generateNestedClasses(className, data, useConstructor);

    let content = `<?php\n\n`;
    content += `namespace ${namespace};\n\n`;
    content += this.generateUseStatements(baseClass);
    content += mainClass;

    if (nestedClasses.trim().length > 0) {
      content += `\n${nestedClasses.trimEnd()}\n`;
    }

    return `${content.trimEnd()}\n`;
  }

  generateForm(fullClassName: string, requestData: JsonRecord, responseData: JsonRecord | JsonRecord[]): GenerateResult {
    const namespace = this.extractNamespace(fullClassName);
    const originalClassName = this.extractClassName(fullClassName);
    const formClassName = originalClassName.endsWith('Form') ? originalClassName : `${originalClassName}Form`;
    const resultClassName = `${formClassName}Result`;

    const formUseConstructor = this.shouldUseConstructor('BaseRequestDTO');
    const formClass = this.generateClass(formClassName, requestData, 'BaseRequestDTO', formUseConstructor);
    const formNested = this.generateNestedClasses(formClassName, requestData, formUseConstructor);

    const responseUseConstructor = this.shouldUseConstructor('BaseResponseDTO');
    const resultClass = this.generateClass(resultClassName, responseData as JsonRecord, 'BaseResponseDTO', responseUseConstructor);
    const resultNested = this.generateNestedClasses(resultClassName, responseData as JsonRecord, responseUseConstructor);

    let content = `<?php\n\n`;
    content += `namespace ${namespace};\n\n`;
    content += this.generateUseStatementsForFormAndResponse();
    content += formClass;
    content += `\n${resultClass}`;

    const nestedBlocks = [formNested, resultNested].filter(block => block.trim().length > 0);
    if (nestedBlocks.length > 0) {
      content += `\n${nestedBlocks.map(block => block.trimEnd()).join('\n')}\n`;
    }

    return `${content.trimEnd()}\n`;
  }

  private extractClassName(fullClassName: string): string {
    const parts = fullClassName.split('\\');
    return parts.at(-1) || 'UnnamedDTO';
  }

  private extractNamespace(fullClassName: string): string {
    const parts = fullClassName.split('\\');
    if (parts.length <= 1) {
      return 'App\\DTO';
    }
    parts.pop();
    return parts.join('\\');
  }

  private determineBaseClass(className: string): 'BaseDTO' | 'BaseRequestDTO' | 'BaseResponseDTO' | 'BaseConfigDTO' {
    if (className.endsWith('Form')) {
      return 'BaseRequestDTO';
    }
    if (className.endsWith('FormResult')) {
      return 'BaseResponseDTO';
    }
    if (className.endsWith('Config') || className.endsWith('ConfigDTO')) {
      return 'BaseConfigDTO';
    }
    return 'BaseDTO';
  }

  private shouldUseConstructor(baseClass: string): boolean {
    if (this.useConstructor !== null) {
      return this.useConstructor;
    }
    return baseClass === 'BaseResponseDTO';
  }

  private generateClass(className: string, data: JsonRecord | JsonValue, baseClass: string, useConstructor: boolean): string {
    const body = useConstructor
      ? this.generatePropertiesWithConstructor(data, className)
      : this.generateProperties(data, className);

    const methods = baseClass === 'BaseRequestDTO'
      ? `\n    public function handle(): ${className.endsWith('Form') ? `${className}Result` : 'mixed'}\n    {\n    }\n`
      : '';

    const sections = [body.trimEnd(), methods.trimEnd()].filter(Boolean).join('\n');
    const inner = sections ? `${sections}\n` : '';
    return `final class ${className} extends ${baseClass}\n{\n${inner}}\n`;
  }

  private generateNestedClasses(parentClassName: string, data: JsonRecord | JsonValue, useConstructor: boolean): string {
    const classes: string[] = [];
    const processed = new Set<string>();
    this.processNestedData(parentClassName, data, classes, processed, useConstructor);
    return classes.join('\n');
  }

  private processNestedData(parentClassName: string, data: JsonRecord | JsonValue, classes: string[], processed: Set<string>, useConstructor: boolean): void {
    if (!this.isPlainObject(data)) {
      return;
    }

    Object.entries(data).forEach(([rawKey, value]) => {
      if (rawKey.startsWith('//')) {
        return;
      }
      const key = this.getActualKey(rawKey);
      const uniqueKey = `${parentClassName}_${key}`;

      if (Array.isArray(value)) {
        const first = value[0];
        if (!first || !this.isPlainObject(first)) {
          return;
        }
        const arrayId = `${uniqueKey}_item`;
        if (processed.has(arrayId)) {
          return;
        }
        const nestedClassName = this.generateNestedClassName(parentClassName, key, true);
        const body = useConstructor
          ? this.generatePropertiesWithConstructor(first, nestedClassName)
          : this.generateProperties(first, nestedClassName);
        classes.push(`final class ${nestedClassName} extends BaseDTO\n{\n${body.trimEnd()}\n}\n`);
        processed.add(arrayId);
        this.processNestedData(nestedClassName, first, classes, processed, useConstructor);
        return;
      }

      if (this.isPlainObject(value)) {
        if (processed.has(uniqueKey)) {
          return;
        }
        const nestedClassName = this.generateNestedClassName(parentClassName, key, false);
        const body = useConstructor
          ? this.generatePropertiesWithConstructor(value, nestedClassName)
          : this.generateProperties(value, nestedClassName);
        classes.push(`final class ${nestedClassName} extends BaseDTO\n{\n${body.trimEnd()}\n}\n`);
        processed.add(uniqueKey);
        this.processNestedData(nestedClassName, value, classes, processed, useConstructor);
      }
    });
  }

  private generateProperties(data: JsonRecord | JsonValue, className: string): string {
    if (!this.isPlainObject(data)) {
      return '';
    }

    let properties = '';
    const descriptions = this.extractDescriptions(data);

    Object.entries(data).forEach(([rawKey, value]) => {
      if (rawKey.startsWith('//')) {
        return;
      }
      const isOptional = this.isOptionalKey(rawKey);
      const key = this.getActualKey(rawKey);
      const type = this.detectType(value, className, key);
      const validationRule = this.getValidationRule(value, type);
      const comments = this.generatePropertyComments(value, type);
      if (descriptions[key]) {
        comments.unshift(String(descriptions[key]));
      }
      if (comments.length > 0) {
        properties += `    /**\n${this.formatComments(comments)}\n     */\n`;
      }
      if (validationRule) {
        properties += `    #[ValidationRules(${validationRule})]\n`;
      }
      const propertyType = this.stripNamespaceFromType(type);
      const phpType = this.getPhpType(propertyType);
      if (isOptional) {
        properties += `    public ${phpType}|null $${key} = null;\n\n`;
      } else {
        properties += `    public ${phpType} $${key};\n\n`;
      }
    });

    return properties;
  }

  private generatePropertiesWithConstructor(data: JsonRecord | JsonValue, className: string): string {
    if (!this.isPlainObject(data) || Object.keys(data).length === 0) {
      return '';
    }

    const descriptions = this.extractDescriptions(data);
    let params = '';

    Object.entries(data).forEach(([rawKey, value]) => {
      if (rawKey.startsWith('//')) {
        return;
      }
      const isOptional = this.isOptionalKey(rawKey);
      const key = this.getActualKey(rawKey);
      const type = this.detectType(value, className, key);
      const validationRule = this.getValidationRule(value, type);
      const comments = this.generatePropertyComments(value, type);
      if (descriptions[key]) {
        comments.unshift(String(descriptions[key]));
      }
      if (comments.length > 0) {
        params += `        /**\n${this.formatComments(comments, '         ')}\n         */\n`;
      }
      if (validationRule) {
        params += `        #[ValidationRules(${validationRule})]\n`;
      }
      const propertyType = this.stripNamespaceFromType(type);
      const phpType = this.getPhpType(propertyType);
      if (isOptional) {
        params += `        public ${phpType}|null $${key} = null,\n`;
      } else {
        params += `        public ${phpType} $${key},\n`;
      }
    });

    return params ? `    public function __construct(\n${params}    ) {}\n` : '';
  }

  private detectType(value: JsonValue, parentClassName: string, key: string): string {
    if (typeof value === 'string') {
      return 'string';
    }
    if (typeof value === 'number') {
      return Number.isInteger(value) ? 'int' : 'float';
    }
    if (typeof value === 'boolean') {
      return 'bool';
    }
    if (value === null) {
      return 'mixed';
    }
    if (Array.isArray(value)) {
      if (value.length === 0) {
        return 'array';
      }
      const first = value[0];
      if (this.isPlainObject(first)) {
        return `${this.generateNestedClassName(parentClassName, key, true)}[]`;
      }
      return `${this.detectType(first as JsonValue, parentClassName, key)}[]`;
    }
    if (this.isPlainObject(value)) {
      return this.generateNestedClassName(parentClassName, key, false);
    }
    return 'mixed';
  }

  private generateNestedClassName(parentClassName: string, key: string, isArrayItem: boolean): string {
    const normalizedParent = parentClassName.replace(/.*\\/, '');
    const capitalizedKey = this.toStudly(key);
    return isArrayItem ? `${normalizedParent}${capitalizedKey}Item` : `${normalizedParent}${capitalizedKey}`;
  }

  private toStudly(value: string): string {
    const replaced = value
      .replace(/[^a-zA-Z0-9]+/g, ' ')
      .trim()
      .toLowerCase();
    const parts = replaced ? replaced.split(' ') : [];
    const studly = parts
      .map(part => part.charAt(0).toUpperCase() + part.slice(1))
      .join('');
    if (studly) {
      return studly;
    }
    const fallback = value.replace(/[^a-zA-Z0-9]/g, '');
    if (!fallback) {
      return '';
    }
    return fallback.charAt(0).toUpperCase() + fallback.slice(1);
  }

  private generateUseStatements(baseClass: string): string {
    const uses: string[] = ['use WebmanTech\\DTO\\BaseDTO;'];
    if (baseClass !== 'BaseDTO') {
      uses.push(`use WebmanTech\\DTO\\${baseClass};`);
    }
    uses.push('use WebmanTech\\DTO\\Attributes\\ValidationRules;');
    uses.push('');
    return `${uses.join('\n')}\n`;
  }

  private generateUseStatementsForFormAndResponse(): string {
    return [
      'use WebmanTech\\DTO\\BaseDTO;',
      'use WebmanTech\\DTO\\BaseRequestDTO;',
      'use WebmanTech\\DTO\\BaseResponseDTO;',
      'use WebmanTech\\DTO\\Attributes\\ValidationRules;',
      ''
    ].join('\n') + '\n';
  }

  private extractDescriptions(data: JsonRecord): Record<string, JsonValue> {
    const descriptions: Record<string, JsonValue> = {};
    Object.entries(data).forEach(([key, value]) => {
      if (key.startsWith('//')) {
        descriptions[key.substring(2)] = value;
      }
    });
    return descriptions;
  }

  private formatComments(lines: string[], indent = '     '): string {
    return lines.map(line => `${indent}* ${line}`).join('\n');
  }

  private stripNamespaceFromType(type: string): string {
    if (type.includes('\\')) {
      if (type.endsWith('[]')) {
        return `${this.stripNamespaceFromType(type.slice(0, -2))}[]`;
      }
      const parts = type.split('\\');
      return parts.at(-1) ?? type;
    }
    return type;
  }

  private getPhpType(type: string): string {
    return type.endsWith('[]') ? 'array' : type;
  }

  private getValidationRule(_: JsonValue, __: string): string | null {
    return null;
  }

  private generatePropertyComments(value: JsonValue, type: string): string[] {
    const comments: string[] = [];
    const example = this.getExampleValue(value);
    if (example !== null) {
      comments.push(`@example ${example}`);
    }
    if (type.endsWith('[]') && type !== 'array') {
      comments.push(`@var ${type}`);
    }
    return comments;
  }

  private getExampleValue(value: JsonValue): string | null {
    if (typeof value === 'string') {
      return value;
    }
    if (typeof value === 'number' || typeof value === 'boolean') {
      return String(value);
    }
    return null;
  }

  private isOptionalKey(key: string): boolean {
    return key.startsWith('?');
  }

  private getActualKey(key: string): string {
    return this.isOptionalKey(key) ? key.slice(1) : key;
  }

  private isPlainObject(value: JsonValue): value is JsonRecord {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
  }
}
