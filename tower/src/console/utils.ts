export function getActionArg(args: string[], actions: string[]): string
{
    if (!args[1]) {
        throw `Action is not specified.`;
    }
    if (!actions.includes(args[1])) {
        throw `Action must be one of [${actions.join(', ')}], but "${args[1]}" given.`;
    }
    return args[1];
}

export function getNamedArg(args: string[], name: string): string
{
    const declaration = args.find(arg => arg.startsWith(`${name}=`));
    if (!declaration) {
        throw `Parameter "${name}" is not specified.`;
    }
    return declaration.slice(name.length + 1);
}

export function getDateArg(arg: string): Date
{
    const date = new Date(arg);
    if (isNaN(date.getTime())) {
        throw `Date must conform the format yyyy-mm-dd, but "${arg}" given.`;
    }
    return date;
}