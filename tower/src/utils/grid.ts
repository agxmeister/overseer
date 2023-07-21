export function getLinesTemplate(lines: Array<string>, size: string = "auto"): string
{
    if (lines.length === 0) {
        return 'auto';
    }
    const template = lines.reduce(
        (acc, line) =>`${acc}${acc ? ' ' : ''}line-${line}-start] ${size} [line-${line}-end`,
        ''
    );
    return `[${template}]`;
}
