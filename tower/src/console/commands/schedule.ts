import {format} from "@/utils/date";
import {ApiUrl} from "@/constants/api";

export default function schedule(args: string[], setSchedule: Function): string[]
{
    const lines = [];
    try {
        const date = getDateArg(args);
        fetch(ApiUrl.SCHEDULE.replace('{date}', format(date)))
            .then(res => res.json())
            .then(data => setSchedule(data));
    } catch (err) {
        lines.unshift(`${err}`);
    }
    return lines;
}

function getDateArg(args: string[]): Date
{
    if (!args[1]) {
        throw `Date is not specified.`;
    }
    const date = new Date(args[1]);
    if (isNaN(date.getTime())) {
        throw `Date must conform the format yyyy-mm-dd, but "${args[1]}" given.`;
    }
    return date;
}
