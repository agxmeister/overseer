export function getColumnsTemplate(columns: Array<string>): string
{
    if (columns.length === 0) {
        return 'auto';
    }
    const template = columns.reduce(
        (acc, column) =>`${acc}${acc ? ' ' : ''}col-${column}-start] auto [col-${column}-end`,
        ''
    );
    return `[${template}]`;
}
