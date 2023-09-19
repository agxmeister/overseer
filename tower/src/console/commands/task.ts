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
                    LinkType.Follows,
                );
                break;
            case Action.Unlink:
                await setters.onUnlink(getLinkId(args, context));
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

function getLinkId(args: string[], context: Context): number
{
    if (!(hasNamedArg(args, 'id') || (hasNamedArg(args, 'from') && hasNamedArg(args, 'to')))) {
        throw 'Either parameter "id" or parameters "from" and "to" must be specified.';
    }
    return hasNamedArg(args, 'id') ?
        getNamedNumberArg(args, 'id') :
        getLinkIdByFromTo(
            getNamedArg(args, 'from'),
            getNamedArg(args, 'to'),
            context.issues,
        );
}

function getLinkIdByFromTo(from: string, to: string, issues: Issue[]): number
{
    const issue = issues.find((issue: Issue) => issue.key === from);
    if (!issue) {
        throw `Task "${from}" not found.`;
    }
    const link = issue.links.inward.find((link: LinkObject) => link.key === to);
    if (!link) {
        throw `Task "${from}" is not linked with task "${to}".`;
    }
    return link.id;
}
