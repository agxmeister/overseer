import {getActionArg, getDateArg, getNamedArg} from "@/console/utils";
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
                await setters.onUnlink(hasLinkIdArg(args) ?
                    getLinkIdArg(args) :
                    getLinkId(
                        getNamedArg(args, 'from'),
                        getNamedArg(args, 'to'),
                        context.issues,
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

function hasLinkIdArg(args: string[]): boolean
{
    const declaration = args.find(arg => arg.startsWith(`id=`));
    return !!declaration;
}

function getLinkIdArg(args: string[]): number
{
    const linkId = parseFloat(getNamedArg(args, 'id'));
    if (isNaN(linkId)) {
        throw `Link ID must be a number, but "${linkId}" given.`
    }
    return linkId;
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
