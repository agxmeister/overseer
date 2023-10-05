import {getActionArg, getDateArg, getNamedArg, getNamedNumberArg, hasNamedArg} from "@/console/utils";
import {format} from "@/utils/date";
import {Issue} from "@/types/Issue";
import {Link as LinkObject, Type as LinkType} from "@/types/Link";
import {Context, Setters} from "@/console/run";

enum Action {
    Resize = "resize",
    Link = "link",
    Unlink = "unlink",
}

export default async function task(args: string[], context: Context, setters: Setters): Promise<string[]>
{
    const lines = [];
    try {
        const action = getActionArg(args, Object.values<string>(Action));
        switch (action) {
            case Action.Resize:
                const taskId = getTaskIdArg(args);
                const beginDate = getDateArg(getNamedArg(args, 'begin'));
                const endDate = getDateArg(getNamedArg(args, 'end'));
                await setters.onTaskResize({taskId: taskId, begin: format(beginDate), end: format(endDate)});
                break;
            case Action.Link:
                await setters.onLink(
                    getNamedArg(args, 'from'),
                    getNamedArg(args, 'to'),
                    LinkType.Schedule,
                );
                break;
            case Action.Unlink:
                await setters.onUnlink(...getUnlinkArgs(args, context));
                break;
        }
    } catch (err) {
        lines.unshift(`${err}`);
    }
    return lines;
}

function getTaskIdArg(args: string[]): string
{
    if (!args[2]) {
        throw `Task id is not specified.`;
    }
    return args[2];
}

function getUnlinkArgs(args: string[], context: Context): [string, string, string]
{
    const from = getNamedArg(args, 'from');
    if (!context.issues.find((issue: Issue) => issue.key === from)) {
        throw `Task "${from}" not found.`;
    }
    const to = getNamedArg(args, 'to');
    if (!context.issues.find((issue: Issue) => issue.key === to)) {
        throw `Task "${to}" not found.`;
    }
    const type = getNamedArg(args, 'type');
    return [from, to, type];
}
