import {getDates} from "@/utils/date";
import {getDateArg} from "@/console/utils";

export default function dates(args: string[], setDates: Function): string[]
{
    const lines = [];
    try {
        const beginDate = getBeginDateArg(args);
        const endDate = getEndDateArg(args);
        setDates(getDates(beginDate, endDate));
    } catch (err) {
        lines.unshift(`${err}`);
    }
    return lines;
}

function getBeginDateArg(args: string[]): Date
{
    if (!args[1]) {
        throw `Begin date is not specified.`;
    }
    return getDateArg(args[1]);
}

function getEndDateArg(args: string[]): Date
{
    if (!args[2]) {
        throw `End date is not specified.`;
    }
    return getDateArg(args[2]);
}
