export function getDateArg(arg: string): Date
{
    const date = new Date(arg);
    if (isNaN(date.getTime())) {
        throw `Date must conform the format yyyy-mm-dd, but "${arg}" given.`;
    }
    return date;
}