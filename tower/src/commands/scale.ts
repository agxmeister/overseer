export default function scale(args: string[], setScale: Function): string[]
{
    const lines = [];
    try {
        const scale = getScaleArg(args);
        setScale(scale);
    } catch (err) {
        lines.unshift(err);
    }
    return lines.map(line => `< ${line}`);
}

function getScaleArg(args: string[]): number
{
    if (!args[1]) {
        throw `Scale is not specified.`;
    }
    const scale = parseFloat(args[1]);
    if (isNaN(scale)) {
        throw `Scale must be a number, but "${args[1]}" given.`
    }
    if (scale <= 0) {
        throw `Scale must be grater than 0, but "${scale}" given.`;
    }
    return scale;
}
