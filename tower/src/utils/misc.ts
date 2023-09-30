export function cleanObject(input: Object): Object
{
    return Object.entries(input)
        .filter(([_, value]) => value)
        .reduce((output, [key, value]) => ({
            ...output,
            [key]: value
        }), {});
}

export const mergeArrays = (a: any[], b: any[], cmp: (a: any, b: any) => boolean) => [...a, ...b.filter((bItem: any) => !a.find((aItem: any) => cmp(aItem, bItem)))];
