export function getLinesTemplate(lines: Array<string>): string
{
    if (lines.length === 0) {
        return 'auto';
    }
    const template = lines.reduce(
        (acc, line) =>`${acc}${acc ? ' ' : ''}line-${line}-start] auto [line-${line}-end`,
        ''
    );
    return `[${template}]`;
}
