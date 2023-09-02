import {format} from "@/utils/date";
import {ApiUrl} from "@/constants/api";
import {Issue} from "@/types/Issue";
import task from "@/console/commands/task";

enum Action {
    Create = "create",
    Apply = "apply",
}

export default function schedule(args: string[], issues: Issue[], schedule: Issue[], setSchedule: Function, onMutate: Function): string[]
{
    const lines = [];
    try {
        const action = getActionArg(args);
        switch (action) {
            case Action.Create:
                const date = getDateArg(args);
                fetch(ApiUrl.SCHEDULE.replace('{date}', format(date)))
                    .then(res => res.json())
                    .then(data => setSchedule(data));
                break;
            case Action.Apply:
                issues.forEach(issue => {
                    lines.unshift(...task(['task', issue.key, `begin=${issue.estimatedBeginDate}`, `end=${issue.estimatedEndDate}`], onMutate));
                });
                break;
        }
    } catch (err) {
        lines.unshift(`${err}`);
    }
    return lines;
}

function getActionArg(args: string[]): string
{
    if (!args[1]) {
        throw `Action is not specified.`;
    }
    if (!Object.values<string>(Action).includes(args[1])) {
        throw `Action must be one of [${Object.values(Action).join(', ')}], but "${args[1]}" given.`;
    }
    return args[1];
}

function getDateArg(args: string[]): Date
{
    if (!args[2]) {
        throw `Date is not specified.`;
    }
    const date = new Date(args[2]);
    if (isNaN(date.getTime())) {
        throw `Date must conform the format yyyy-mm-dd, but "${args[1]}" given.`;
    }
    return date;
}
