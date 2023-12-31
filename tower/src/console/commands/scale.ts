import {Setters} from "@/console/run";

export default async function scale(args: string[], setters: Setters): Promise<string[]>
{
    const lines = [];
    try {
        const scale = getScaleArg(args);
        setters.setScale(scale);
    } catch (err) {
        lines.unshift(`${err}`);
    }
    return lines;
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
