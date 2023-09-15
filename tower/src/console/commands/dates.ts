import {getDates} from "@/utils/date";
import {getDateArg} from "@/console/utils";
import {Setters} from "@/console/run";

export default async function dates(args: string[], setters: Setters): Promise<string[]>
{
    const lines = [];
    try {
        const beginDate = getBeginDateArg(args);
        const endDate = getEndDateArg(args);
        setters.setDates(getDates(beginDate, endDate));
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
