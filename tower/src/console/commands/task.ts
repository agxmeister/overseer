import {getActionArg, getDateArg, getNamedArg} from "@/console/utils";
import {format} from "@/utils/date";
import {Issue} from "@/types/Issue";
import {Link as LinkObject} from "@/types/Link";

enum Action {
    Resize = "resize",
    Link = "link",
    Unlink = "unlink",
}

export default async function task(args: string[], issues: Issue[], onTaskResize: Function, onLink: Function, onUnlink: Function): Promise<string[]>
{
    const lines = [];
    try {
        const action = getActionArg(args, Object.values<string>(Action));
        switch (action) {
            case Action.Resize:
                const taskId = getTaskIdArg(args);
                const beginDate = getDateArg(getNamedArg(args, 'begin'));
                const endDate = getDateArg(getNamedArg(args, 'end'));
                await onTaskResize({taskId: taskId, begin: format(beginDate), end: format(endDate)});
                break;
            case Action.Link:
                await onLink(
                    getNamedArg(args, 'from'),
                    getNamedArg(args, 'to')
                );
                break;
            case Action.Unlink:
                await onUnlink(getLinkId(
                    getNamedArg(args, 'from'),
                    getNamedArg(args, 'to'),
                    issues,
                ));
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
        throw `Task ID is not specified.`;
    }
    return args[2];
}

function getLinkId(from: string, to: string, issues: Issue[]): number
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
