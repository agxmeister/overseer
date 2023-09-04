export function clean(input: Object): Object
{
    return Object.entries(input)
        .filter(([_, value]) => value)
        .reduce((output, [key, value]) => ({
            ...output,
            [key]: value
        }), {});
}
